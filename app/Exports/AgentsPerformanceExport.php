<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

// class AgentPerformanceExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
class AgentsPerformanceExport implements FromView, WithStyles, ShouldAutoSize, WithTitle, WithColumnWidths
{

    protected $user;
    protected $ticketsData;
    protected $agentsTicketsCountByStatus;
    protected $dateRange;
    protected $sheetAverages;
    protected $viewReportData;

    // public function __construct(array $users)
    public function __construct($user, $ticketsData, $agentsTicketsCountByStatus, $dateRange, $sheetAverages, $viewReportData)
    {
        $this->user                       = $user;
        $this->ticketsData                = $ticketsData;
        $this->agentsTicketsCountByStatus = $agentsTicketsCountByStatus;
        $this->dateRange                  = $dateRange;
        $this->sheetAverages              = $sheetAverages;
        $this->viewReportData             = $viewReportData;

        // dump($this->viewReportData);
        // dd($this->viewReportData['rowDataAverages']);

        // dump($this->user);
        // dump($this->ticketsData);
        // dd($this->agentsTicketsCountByStatus);
        // dump($this->dateRange);
        // dd($this->sheetAverages);
    }

    public function view(): View
    {

        if ( !empty( $this->dateRange ) && is_array($this->dateRange) )
        {

            $tmpDateRange = $this->dateRange;

            foreach( $tmpDateRange as $key => $date )
            {
                $tmpDateRange[$key] = \Carbon\Carbon::parse($date)->format('M d, Y');
            }

            $this->dateRange = $tmpDateRange;

        }
        
        if ( $this->sheetAverages )
        {
            return view('exports.users', [
                'user'                       => $this->user,
                'tickets'                    => $this->ticketsData,
                'agentsTicketsCountByStatus' => $this->agentsTicketsCountByStatus,
                'dateRange'                  => !empty( $this->dateRange ) ? implode(' - ', $this->dateRange) : '',
                'viewReportData'             => $this->viewReportData
            ]);
        }
        else
        {
            return view('exports.users', [
                'user'                       => $this->user,
                'tickets'                    => $this->ticketsData,
                'agentsTicketsCountByStatus' => $this->agentsTicketsCountByStatus,
                'dateRange'                  => !empty( $this->dateRange ) ? implode(' - ', $this->dateRange) : '',
                'viewReportData'             => $this->viewReportData
            ]);
        }

    }

    /*public function array(): array
    {
        return $this->user;
    }*/

    /*public function headings()
    {
        return ["name", "email"];
    }*/

    /*public function headings(): array
    {
        return [
            '#',
            'Name',
            'Email',
            'Created at',
            'Updated at'
        ];
    }*/

    public function columnWidths(): array
    {
        return [
            'B' => 25,
            'C' => 20,
            'D' => 20,          
            'E' => 20,        
            'F' => 15,        
            'G' => 15,        
            'H' => 15,        
            'I' => 15,        
            'J' => 15,        
            'K' => 15,        
            'L' => 15,        
            'R' => 15,        
            'S' => 15,        
            'T' => 15,        
            'U' => 15,        
            'V' => 15,        
            'W' => 15,        
        ];
    }

    public function styles(Worksheet $sheet)
    {
    	$sheet->getStyle('1')->getFont()->setBold(true);

        /*return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true], 'height' => '40px'],

            // Styling a specific cell by coordinate.
            'B2' => ['font' => ['italic' => true]],

            // Styling an entire column.
            'C'  => ['font' => ['size' => 16]],
        ];*/
    }

    public function title(): string
    {
        return $this->user->name;
    }

}
