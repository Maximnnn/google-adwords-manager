<?php namespace Controllers;


use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SharedCriterionService;

class Criteria extends BaseController {

    const PAGE_LIMIT = 500;

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request) {
        if ($request->validate(Rules::create([
            'adGroups' => 'required|notempty'
        ]), 'post', false)->failed()) return Response::error('adGroups required');

        /**@var $adGroupCriterionService SharedCriterionService*/
        $adGroupCriterionService = $adWordsServices->get($session, SharedCriterionService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(['Id', 'Criteria', 'SharedSetId']);
        $selector->setPredicates(
            [
                new Predicate('Id', PredicateOperator::IN, $request->post('adGroups')),
            ]
        );
        $selector->setPredicates([
                new Predicate('CriteriaType', PredicateOperator::IN, [
                    CriterionType::KEYWORD
                ])]
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


                foreach ($page->getEntries() as $adGroupCriterion) {
                    {
                        /**@var $keyword Keyword*/
                        $keyword = $adGroupCriterion->getCriterion();
                        $return[] = [
                            'id' => $keyword->getId(),
                            'negative' => $adGroupCriterion->getNegative(),
                            'text' => $keyword->getText(),
                            'match_type' => $keyword->getMatchType(),
                            'shared_set_id' => $adGroupCriterion->getSharedSetId()
                        ];
                    }
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $return;
    }

}