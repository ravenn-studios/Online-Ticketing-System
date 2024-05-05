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

class AgentsMultisheetSummaryExport implements WithMultipleSheets
{

	use Exportable;

	private $dateRange = [];
	private $usersTicketsSummaryData = [];


    // public function __construct(int $userIds)
    public function __construct($dateRange, array $usersTicketsSummaryData)
    {
		$this->dateRange               = $dateRange == null ? '' : $dateRange;
		$this->usersTicketsSummaryData = $usersTicketsSummaryData;
    }

    public function sheets(): array
    {

        $sheets = [];
        

		$sheets['Performance Summary Report - ' . implode('-', $this->dateRange)] = new AgentsPerformanceSummaryExport($this->dateRange, $this->usersTicketsSummaryData);


        return $sheets;

    }

}