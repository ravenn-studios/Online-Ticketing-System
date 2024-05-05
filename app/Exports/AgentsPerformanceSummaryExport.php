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
class AgentsPerformanceSummaryExport implements FromView, WithStyles, ShouldAutoSize, WithTitle, WithColumnWidths
{

    protected $dateRange;
    protected $usersTicketsSummaryData;

    // public function __construct(array $users)
    public function __construct($dateRange, $usersTicketsSummaryData)
    {

        $this->dateRange               = $dateRange;
        $this->usersTicketsSummaryData = $usersTicketsSummaryData;

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

        /*dump($this->usersTicketsSummaryData);
        dump(array_keys($this->usersTicketsSummaryData[0]['tickets_count']));
        dd(array_column($this->usersTicketsSummaryData[0]['tickets_count'], 'tickets_pending'));*/
        // dump($this->dateRange);
        // dump( \Carbon\Carbon::parse($this->dateRange[0])->format('F') );
        // $d = \Carbon\Carbon::parse($this->dateRange[0])->format('F d') . ' - ' . \Carbon\Carbon::parse($this->dateRange[1])->format('F d');
        // dd($d);
        return view('exports.performance-summary-report', [
            'dateRange'               => !empty( $this->dateRange ) ? $this->dateRange : '',
            'usersTicketsSummaryData' => $this->usersTicketsSummaryData
        ]);
        

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
            'A' => 18,       
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
        return 'Performance Summary Report';
    }

}
