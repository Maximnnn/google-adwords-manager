<?php namespace Controllers;

use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdManager\v201711\MinuteOfHour;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\AdSchedule;
use Google\AdsApi\AdWords\v201809\cm\Campaign;
use Google\AdsApi\AdWords\v201809\cm\CampaignCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\CampaignCriterionService;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\KeywordMatchType;
use Google\AdsApi\AdWords\v201809\cm\NegativeCampaignCriterion;
use Google\AdsApi\AdWords\v201809\cm\Operator;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;


class Campaigns extends BaseController
{
    const PAGE_LIMIT = 500;

    public function uploadNegativeKeywords(Request $request) {
        $session = $this->getSession($this->getOAuth2());
        $services = new AdWordsServices();

        $user_keywords = $request->post('keywords');
        $campaign_id = $request->post('campaign_id');

        $operations = [];

        /**@var $service CampaignCriterionService*/
        $service = $services->get($session, CampaignCriterionService::class);

        foreach ($user_keywords as $keyword) {
            if ($keyword[0] == '[') {
                $type = KeywordMatchType::EXACT;
                $keyword = substr($keyword, 1, -1);
            } else if ($keyword[0] == '"') {
                $type = KeywordMatchType::PHRASE;
                $keyword = substr($keyword, 1, -1);
            } else {
                $type = KeywordMatchType::BROAD;
            }

            $negativeCampaignCriterion = new NegativeCampaignCriterion();
            $keywordObj = New Keyword();
            $keywordObj->setText($keyword);
            $keywordObj->setMatchType($type);

            $negativeCampaignCriterion->setCriterion($keywordObj);
            $negativeCampaignCriterion->setCampaignId($campaign_id);

            $operation = new CampaignCriterionOperation();
            $operation->setOperand($negativeCampaignCriterion);
            $operation->setOperator(Operator::ADD);

            $operations[] = $operation;
        }

        $result = $service->mutate($operations);

        return Response::getInstance($result)->type('json');
    }

    /**
     * @param  $request Request need to contain post values:
     *             campaign_id => integer,
     *             keywords => array of keywords ('[keyword]' //exact, '"keyword"' //phrase, 'keyword' //broad)
     * @return Response
     * @throws
     */
    public function removeNegativeKeywords(Request $request) {
        $session = $this->getSession($this->getOAuth2());
        $services = new AdWordsServices();


        $request->validate(Rules::create([
            'campaign_id' => 'required|notempty|int',
            'keywords' => 'required|notempty'
        ]), 'post');

        $campaign_id = $request->post('campaign_id', 'int');
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

        /**@var $service CampaignCriterionService*/
        $service = $services->get($session, CampaignCriterionService::class);

        //EXACT
        if (!empty($exact_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('CampaignId', PredicateOperator::IN, [$campaign_id]),
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
                    )
                ]
            );

            $page = $service->get($selector);
            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    if ($criterion->getIsNegative()) $criteria_to_delete[] = $criterion;
                };
            }
        }

        //PHRASE
        if (!empty($phrase_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('CampaignId', PredicateOperator::IN, [$campaign_id]),
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
                    )
                ]
            );

            $page = $service->get($selector);

            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    if ($criterion->getIsNegative()) $criteria_to_delete[] = $criterion;
                };
            }
        }

        //BROAD
        if (!empty($broad_kws)) {
            $selector = new Selector();
            $selector->setFields(['KeywordText']);
            $selector->setPredicates(
                [
                    new Predicate('CampaignId', PredicateOperator::IN, [$campaign_id]),
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
                    )
                ]
            );

            $page = $service->get($selector);
            if ($page) {
                $entries = $page->getEntries();
                if ($entries) foreach ($entries as $criterion) {
                    if ($criterion->getIsNegative()) $criteria_to_delete[] = $criterion;
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
            $result = $service->mutate($operations);
            $result = ['deleted' => count($result)];
        } else
            $result = ['deleted' => 0];

        return Response::getInstance($result)->type('json');
    }

    protected function runExample(
        AdWordsServices $adWordsServices,
        AdWordsSession $session,
        Request $request
    ) {
        $return = [];
        /**@var $campaignService CampaignService*/
        $campaignService = $adWordsServices->get($session, CampaignService::class);
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name', 'Status', 'TrackingUrlTemplate'])
                 ->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)])
                 ->setPaging(new Paging(0, self::PAGE_LIMIT));
        $totalNumEntries = 0;
        do {
            // Make the get request.
            $page = $campaignService->get($selector);
            // Display results.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                /**@var $campaign Campaign*/
                foreach ($page->getEntries() as $campaign) {
                    $return[] = [
                        'id' => $campaign->getId(),
                        'name' => $campaign->getName(),
                        'status' => $campaign->getStatus(),
                        'pid' => $this->getPidFromTemplate($campaign->getTrackingUrlTemplate())
                    ];
                }
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        //$return['total_count'] = $totalNumEntries;

        return $return;
    }

    public function getScheduleForCampaigns(Request $request) {

        $request->validate(Rules::create([
            'campaignIds' => 'required|notempty'
        ]), 'post');

        $campaigns = $request->post('campaignIds');

        $session = $this->getSession($this->getOAuth2());
        $services = new AdWordsServices();



        $return = [];
        /**@var $campaignService CampaignCriterionService*/
        $campaignService = $services->get($session, CampaignCriterionService::class);
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['CampaignId'])
            ->setPredicates([
                    new Predicate(
                        'CampaignId',
                        PredicateOperator::IN,
                        $campaigns
                    ),
                    new Predicate(
                        'CriteriaType',
                        PredicateOperator::IN,
                        [CriterionType::AD_SCHEDULE]
                    )
                ]
            );


        $page = $campaignService->get($selector);
        $return = [];
        if ($page->getEntries() !== null) {
            /**@var $criterion \Google\AdsApi\AdWords\v201809\cm\CampaignCriterion  */
            foreach ($page->getEntries() as $criterion) {
                /**@var $c AdSchedule*/
                $c = $criterion->getCriterion();
                $return[] = [
                    'campaign_id' => $criterion->getCampaignId(),
                    'day'         => $c->getDayOfWeek(),
                    'start'       => $this->getTimeFromSchedule($c->getStartHour(), $c->getStartMinute()),
                    'end'         => $this->getTimeFromSchedule($c->getEndHour(), $c->getEndMinute()),
                    'schedule_id' => $c->getId()
                ];
            }
        }


        return Response::getInstance($return);
    }

    protected function getTimeFromSchedule(int $hour, string $minute) {
        switch ($minute) {
            case MinuteOfHour::ZERO:
                $m = 0;
                break;
            case MinuteOfHour::FIFTEEN:
                $m = 15;
                break;
            case MinuteOfHour::THIRTY:
                $m = 30;
                break;
            case MinuteOfHour::FORTY_FIVE:
                $m = 45;
                break;
            default:
                $m = 0;
                break;
        }

        return $hour . ':' . $m;
    }

    protected function getPidFromTemplate($urlTemplate) {
        $parts = parse_url($urlTemplate);
        parse_str($parts['query'] ?? '', $query);
        $pid = $query['pid'] ?? '';
        return $pid;
    }
}