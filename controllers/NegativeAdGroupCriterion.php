<?php namespace Controllers;


use App\Http\BaseController;
use App\Http\Request;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\CriterionUse;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;

class NegativeAdGroupCriterion extends BaseController
{
    const PAGE_LIMIT = 500;
    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request)
    {
        /**@var $adGroupCriterionService AdGroupCriterionService*/
        $adGroupCriterionService = $adWordsServices->get($session, AdGroupCriterionService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(['KeywordText']);
        $selector->setPredicates(
            [
                new Predicate('AdGroupId', PredicateOperator::IN, $request->post('adGroupIds')),
                new Predicate(
                    'CriteriaType',
                    PredicateOperator::IN,
                    [CriterionType::KEYWORD]
                ),
                new Predicate(
                    'CriterionUse',
                    PredicateOperator::IN,
                    [CriterionUse::NEGATIVE]
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


                foreach ($page->getEntries() as $criterion) {
                    {
                        /**@var $a Keyword*/
                        $a = $criterion->getCriterion();
                        $return[] = [
                            'keyword_id' => $a->getId(),
                            'adgroup_id' => $criterion->getAdGroupId(),
                            'negative' => true,
                            'text' => $a->getText(),
                            'criterion_type' => $a->getCriterionType(),
                            'type' => $a->getType(),
                            'match_type' => $a->getMatchType()
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