<?php namespace Controllers;

use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\CampaignSharedSetService;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;

class CampaignsSharedSet extends BaseController
{
    const PAGE_LIMIT = 500;

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request) {

        if ($request->validate(Rules::create([
            'campaignIds' => 'required|notempty'
        ]), 'post', false)->failed()) throw new ValidatorException('campaignIds required');

        /**@var $adGroupCriterionService CampaignSharedSetService*/
        $adGroupCriterionService = $adWordsServices->get($session, CampaignSharedSetService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(['CampaignId', 'SharedSetName', 'SharedSetType', 'CampaignName', 'Status']);
        $selector->setPredicates(
            [
                new Predicate('CampaignId', PredicateOperator::IN, $request->post('campaignIds')),
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


                foreach ($page->getEntries() as $campaignSharedSet) {
                    {
                        $return[] = [
                            'shared_set_id' => $campaignSharedSet->getSharedSetId(),
                            'campaign_id' => $campaignSharedSet->getCampaignId(),
                            'name' => $campaignSharedSet->getSharedSetName(),
                            'type' => $campaignSharedSet->getSharedSetType(),
                            'status' => $campaignSharedSet->getStatus()
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