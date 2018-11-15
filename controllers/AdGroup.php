<?php namespace Controllers;


use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\CampaignCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\CriterionUse;
use Google\AdsApi\AdWords\v201809\cm\KeywordMatchType;
use Google\AdsApi\AdWords\v201809\cm\Operator;
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
                        'adgroup_id' => $adGroup->getId(),
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

    public function removeNegativeKeywords(Request $request) {

        $session = $this->getSession($this->getOAuth2());
        $services = new AdWordsServices();

        $request->validate(Rules::create([
            'adgroup_id' => 'required|notempty|int',
            'keywords' => 'required|notempty'
        ]), 'post');

        $adgroupId = $request->post('adgroup_id', 'int');
        $keywords = $request->post('keywords');

        $phrase_kws = [];
        $exact_kws = [];
        $broad_kws = [];
        $criteria_to_delete = [];
        $operations = [];

        foreach ($keywords as $keyword) {
            if ($keyword[0] == '[') {
                $exact_kws[] = str_replace(['[', ']'], '', $keyword);
            } else if ($keyword[0] == '"') {
                $phrase_kws[] = str_replace('"', '', $keyword);
            } else {
                $broad_kws[] = $keyword;
            }
        }

        /**@var $service AdGroupCriterionService*/
        $service = $services->get($session, AdGroupCriterionService::class);

        //EXACT
        if (!empty($exact_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('AdGroupId', PredicateOperator::IN, [$adgroupId]),
                    new Predicate(
                        'CriteriaType',
                        PredicateOperator::IN,
                        [CriterionType::KEYWORD]
                    ),
                    new Predicate(
                        'KeywordMatchType',
                        PredicateOperator::IN,
                        [KeywordMatchType::EXACT]
                    ),
                    new Predicate(
                        'KeywordText',
                        PredicateOperator::IN,
                        $exact_kws
                    ),
                    new Predicate(
                        'CriterionUse',
                        PredicateOperator::IN,
                        [CriterionUse::NEGATIVE]
                    )
                ]
            );

            $page = $service->get($selector);
            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    $criteria_to_delete[] = $criterion;
                };
            }
        }

        //PHRASE
        if (!empty($phrase_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('AdGroupId', PredicateOperator::IN, [$adgroupId]),
                    new Predicate(
                        'CriteriaType',
                        PredicateOperator::IN,
                        [CriterionType::KEYWORD]
                    ),
                    new Predicate(
                        'KeywordMatchType',
                        PredicateOperator::IN,
                        [KeywordMatchType::PHRASE]
                    ),
                    new Predicate(
                        'KeywordText',
                        PredicateOperator::IN,
                        $phrase_kws
                    ),
                    new Predicate(
                        'CriterionUse',
                        PredicateOperator::IN,
                        [CriterionUse::NEGATIVE]
                    )
                ]
            );

            $page = $service->get($selector);

            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    $criteria_to_delete[] = $criterion;
                };
            }
        }

        //BROAD
        if (!empty($broad_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('AdGroupId', PredicateOperator::IN, [$adgroupId]),
                    new Predicate(
                        'CriteriaType',
                        PredicateOperator::IN,
                        [CriterionType::KEYWORD]
                    ),
                    new Predicate(
                        'KeywordMatchType',
                        PredicateOperator::IN,
                        [KeywordMatchType::BROAD]
                    ),
                    new Predicate(
                        'KeywordText',
                        PredicateOperator::IN,
                        $broad_kws
                    ),
                    new Predicate(
                        'CriterionUse',
                        PredicateOperator::IN,
                        [CriterionUse::NEGATIVE]
                    )
                ]
            );

            $page = $service->get($selector);
            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    $criteria_to_delete[] = $criterion;
                };
            }
        }

        foreach ($criteria_to_delete as $criterion) {
            $operation = new CampaignCriterionOperation();
            $operation->setOperand($criterion);
            $operation->setOperator(Operator::REMOVE);

            $operations[] = $operation;
        }

        if (!empty($operations)) {
            $service->mutate($operations);
            $result = ['deleted' => count($operations)];
        } else
            $result = ['deleted' => 0];

        return Response::getInstance($result)->type('json');
    }
}