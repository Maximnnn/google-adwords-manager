<?php namespace Controllers;


use App\Exceptions\ValidatorException;
use App\Http\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Validation\Rules;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SharedCriterionService;

class SharedCriterion extends BaseController
{
    const PAGE_LIMIT = 500;


    public function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request) {
        /**@var $adGroupCriterionService SharedCriterionService*/
        $adGroupCriterionService = $adWordsServices->get($session, SharedCriterionService::class);
        // Create a selector to select all keywords for the specified ad group.
        $selector = new Selector();
        $selector->setFields(['SharedSetId', 'KeywordText']);
        $sharedSetIds = $request->post('shared_set_id');
        if (is_array($sharedSetIds)) {
            $selector->setPredicates(
                [
                    new Predicate('SharedSetId', PredicateOperator::IN, $sharedSetIds),
                    new Predicate('CriteriaType', PredicateOperator::IN, [CriterionType::KEYWORD])
                ]
            );
        }
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


                foreach ($page->getEntries() as $sharedCriterion) {
                    {
                        if (get_class($sharedCriterion) == 'Google\AdsApi\AdWords\v201809\cm\SharedCriterion') {
                            /**@var $a Keyword*/
                            $a = $sharedCriterion->getCriterion();
                            $return[] = [
                                'shared_set_id' => $sharedCriterion->getSharedSetId(),
                                'negative' => $sharedCriterion->getNegative(),
                                'keyword_id' => $a->getId(),
                                'text' => $a->getText(),
                                'criterion_type' => $a->getCriterionType(),
                                'type' => $a->getType(),
                                'match_type' => $a->getMatchType()
                            ];

                        }

                        //pn($sharedCriterion);
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