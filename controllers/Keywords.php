<?php namespace Controllers;

use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\BiddableAdGroupCriterion;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\CriterionUse;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

class Keywords extends BaseController
{
    const PAGE_LIMIT = 500;

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request) {

        if ($request->validate(Rules::create([
            'adGroups' => 'required|notempty'
        ]), 'post', false)->failed()) throw new ValidatorException('adGroups required');

        /**@var $adGroupCriterionService AdGroupCriterionService*/
        $adGroupCriterionService = $adWordsServices->get($session, AdGroupCriterionService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(
            ['Id', 'CriteriaType', 'KeywordMatchType', 'KeywordText', 'Status']
        );
        $selector->setOrdering([new OrderBy('KeywordText', SortOrder::ASCENDING)]);
        $selector->setPredicates(
            [
                new Predicate('AdGroupId', PredicateOperator::IN, $request->post('adGroups')),
                new Predicate(
                    'CriteriaType',
                    PredicateOperator::IN,
                    [CriterionType::KEYWORD]
                ),
                new Predicate(
                    'CriterionUse',
                    PredicateOperator::IN,
                    [CriterionUse::BIDDABLE]
                )
            ]
        );
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $totalNumEntries = 0;
        $return = [];

        do {
            // Retrieve keywords one page at a time, continuing to request pages
            // until all keywords have been retrieved.
            $page = $adGroupCriterionService->get($selector);
            // Print out some information for each keyword.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                /**@var $adGroupCriterion BiddableAdGroupCriterion*/
                foreach ($page->getEntries() as $adGroupCriterion) {
                    /**@var $keyword Keyword*/
                    $keyword = $adGroupCriterion->getCriterion();

                    $return[] = [
                        'id' => $keyword->getId(),
                        'match_type' => $keyword->getMatchType(),
                        'type' => $keyword->getType(),
                        'text' => $keyword->getText(),
                        'status' => $adGroupCriterion->getUserStatus(),
                        'adgroup_id' => $adGroupCriterion->getAdGroupId(),
                        'criterion_type' => $keyword->getCriterionType()
                    ];
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        return $return;
    }

}