<?php namespace Controllers;

use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAd;
use Google\AdsApi\AdWords\v201809\cm\AdGroupAdService;
use Google\AdsApi\AdWords\v201809\cm\AdType;
use Google\AdsApi\AdWords\v201809\cm\ExpandedTextAd;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

class Ads extends BaseController
{
    const PAGE_LIMIT = 500;

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request) {
        if ($request->validate(Rules::create([
            'adGroups' => 'required|notempty'
        ]), 'post', false)->failed()) throw new ValidatorException('adGroups required');
        /**@var $adGroupService AdGroupAdService*/
        $adGroupService = $adWordsServices->get($session, AdGroupAdService::class);
        // Create a selector to select all ad groups for the specified campaign.
        $selector = new Selector();
        $selector->setFields(['Id', 'Status', 'Url', 'CreativeFinalUrls', 'HeadlinePart1', 'HeadlinePart2', 'Headline', 'Description', 'Description2']);
        $selector->setOrdering([new OrderBy('Id', SortOrder::ASCENDING)]);
        $selector->setPredicates([
            new Predicate('AdGroupId', PredicateOperator::IN, $request->post('adGroups')),
            new Predicate('AdType',PredicateOperator::IN, [AdType::EXPANDED_TEXT_AD])
        ]);
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
                    /**@var $adGroup AdGroupAd*/
                    /**@var $ad ExpandedTextAd*/
                    $ad = $adGroup->getAd();

                    $arr = [
                        'id' => $ad->getId(),
                        'adgroup_id' => $adGroup->getAdGroupId(),
                        'status' => $adGroup->getStatus(),
                        'type' => $ad->getAdType(),
                        'url' => $ad->getFinalUrls(),
                    ];
                    if (get_class($ad) == 'Google\AdsApi\AdWords\v201809\cm\ExpandedTextAd') {
                        $arr['description'] = $ad->getDescription();
                        $arr['description2'] = $ad->getDescription2();
                        $arr['headlines'] = [$ad->getHeadlinePart1(),$ad->getHeadlinePart2(),$ad->getHeadlinePart3()];
                    }

                    $result[] = $arr;
                }
            }
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $result;
    }

}