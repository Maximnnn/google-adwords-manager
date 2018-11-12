<?php
namespace Controllers\Report;

use App\Http\BaseController;
use Google\AdsApi\AdWords\AdWordsServices;
use App\Http\Request;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\AdWords\v201802\cm\SortOrder;
use Google\AdsApi\AdWords\v201802\cm\OrderBy;

class ReportClicks extends BaseController
{

    protected function runExample(AdWordsServices $adWordsServices, AdWordsSession $session, Request $request)
    {
        $selector = new Selector();
        $selector->setFields(
            [
                'Date',
                'GclId',
                'CreativeId',
            ]
        );

        // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'Criteria performance report #' . uniqid()
        );
        $reportDefinition->setDateRangeType(
            ReportDefinitionDateRangeType::TODAY
        );
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CLICK_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::CSV);

        $reportDownloader = new ReportDownloader($session);
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
            $reportDefinition,
            $reportSettingsOverride
        );
        //$reportDownloadResult->saveToFile($filePath);
         $reportAsString = $reportDownloadResult->getAsString();

         $data = [];
         foreach(preg_split("/((\r?\n)|(\r\n?))/", $reportAsString) as $key => $line){

             if($key !== 0 && $key !== 1)
                $data['report'][] = explode(',', $line);

         }

         unset($data['report'][count($data['report'])-1]);

         return $data;

    }

}