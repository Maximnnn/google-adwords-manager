<?php
/**
 * Created by PhpStorm.
 * User: musakovs
 * Date: 11/6/18
 * Time: 11:22 AM
 */

namespace Controllers;


use App\Http\BaseController;
use App\Http\Request;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\CampaignCriterionService;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\CriterionUse;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;

class NegativeCampaignCriterion extends BaseController
{
    const PAGE_LIMIT = 500;

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request)
    {
        /**@var $adGroupCriterionService CampaignCriterionService*/
        $adGroupCriterionService = $adWordsServices->get($session, CampaignCriterionService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(['KeywordText']);
        $selector->setPredicates(
            [
                new Predicate('CampaignId', PredicateOperator::IN, $request->post('campaignIds')),
                new Predicate(
                    'CriteriaType',
                    PredicateOperator::IN,
                    [CriterionType::KEYWORD]
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
                        if (get_class($criterion) == 'Google\AdsApi\AdWords\v201809\cm\NegativeCampaignCriterion') {
                            /**@var $a Keyword */
                            $a = $criterion->getCriterion();
                            if (get_class($a) == 'Google\AdsApi\AdWords\v201809\cm\Keyword') {
                                $arr = [];
                                $arr['negative'] = $criterion->getIsNegative();
                                $arr['id'] = $a->getId();
                                $arr['campaign_id'] = $criterion->getCampaignId();
                                $arr['text'] = $a->getText();
                                $arr['criterion_type'] = $a->getCriterionType();
                                $arr['type'] = $a->getType();
                                $arr['match_type'] = $a->getMatchType();
                                $return[] = $arr;
                            }
                        }
                    }
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return ($return);

    }


}