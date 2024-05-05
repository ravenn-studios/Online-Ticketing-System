<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;

use App\User;
use App\Role;
use App\Ticket;
use App\Message;
use App\TicketType;
use App\TicketPriority;
use App\TicketStatus;
use App\CustomVariable;
use App\EmailTemplate;
use App\AssignedTicket;
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Category;
use App\UserPerformanceLog;
use App\EmailSupportAddress;
use DateTime;
use DateTimeZone;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgentsMultisheetCategorizedExport implements WithMultipleSheets
{

	use Exportable;

    private $dateRange                  = [];
    private $usersCategorizedReportData = [];
    // private $parentCategoriesData    = [];
    private $parentCategoriesId         = [];


    // public function __construct(int $userIds)
    // public function __construct($dateRange, array $usersCategorizedReportData, array $parentCategoriesData)
    public function __construct($dateRange, array $usersCategorizedReportData, array $parentCategoriesId)
    {
        $this->dateRange                  = $dateRange == null ? '' : $dateRange;
        $this->usersCategorizedReportData = $usersCategorizedReportData;
        // $this->parentCategoriesData    = $parentCategoriesData;
        $this->parentCategoriesId         = $parentCategoriesId;
    }

    public function sheets(): array
    {

        $sheets = $parentCategoryIds = [];
        
        // foreach($this->usersCategorizedReportData as $data)
        // {
            // $index             = array_search( $data['id'], array_column($this->parentCategoriesData, 'user_id') );
            // $parentCategoryIds = $this->parentCategoriesData[$index]['parent_category_ids'];

            // $sheets[$data['name']] = new AgentsPerformanceCategorizedExport($this->dateRange, $data['categories'], $data['name'], $parentCategoryIds);
            // $sheets[$data['name']] = new AgentsPerformanceCategorizedExport($this->dateRange, $data['categories'], $data['name']);
        // }

            $sheets[] = new AgentsPerformanceCategorizedExport($this->dateRange, $this->usersCategorizedReportData, $this->parentCategoriesId);


        return $sheets;

    }

}