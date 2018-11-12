<?php namespace Controllers;


use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

class AdGroup extends BaseController {
    const PAGE_LIMIT = 500;

    protected function runExample(
        AdWordsServices $adWordsServices,
        AdWordsSession $session,
        Request $request
    ) {
        if ($request->validate(Rules::create([
            'campaigns' => 'required|notempty'
        ]), 'post', false)->failed()) throw new ValidatorException('required campaigns');

        /**@var $adGroupService AdGroupService*/
        $adGroupService = $adWordsServices->get($session, AdGroupService::class);
        // Create a selector to select all ad groups for the specified campaign.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name', 'Status', 'CampaignGroupId', 'BaseCampaignId']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPredicates(
          [new Predicate('CampaignId', PredicateOperator::IN, $request->post('campaigns'))]
        );
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $totalNumEntries = 0;

        $result = [];

        do {
            // Retrieve ad groups one page at a time, continuing to request pages
            // until all ad groups have been retrieved.
            $page = $adGroupService->get($selector);
            // Print out some information for each ad group.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroup) {
                    $result[] = [
                        'id' => $adGroup->getId(),
                        'name' => $adGroup->getName(),
                        'status' => $adGroup->getStatus(),
                        'campaign_id' => $adGroup->getCampaignId()
                    ];
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $result;
    }
}