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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

// class AgentsPerformanceCategorizedExport implements FromView, WithStyles, ShouldAutoSize, WithTitle, WithColumnWidths
class AgentsPerformanceCategorizedExport implements FromView, WithStyles, ShouldAutoSize, WithColumnWidths, WithEvents
{

    protected $dateRange;
    protected $usersCategorizedReportData;
    protected $parentCategoriesId;
    // protected $name;
    // protected $parentCategoryIds;

    // public function __construct(array $users)
    // public function __construct($dateRange, $usersCategorizedReportData, $name, $parentCategoryIds)
    public function __construct($dateRange, array $usersCategorizedReportData, array $parentCategoriesId)
    {

        $this->dateRange                  = $dateRange;
        $this->usersCategorizedReportData = $usersCategorizedReportData;
        $this->parentCategoriesId         = $parentCategoriesId;
        // $this->name                       = $name;
        // $this->parentCategoryIds          = $parentCategoryIds;

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
        
        // dd($this->usersCategorizedReportData);
        return view('exports.categorized-report-export-data', [
            'dateRange'                  => !empty( $this->dateRange ) ? $this->dateRange : '',
            'usersCategorizedReportData' => $this->usersCategorizedReportData,
            'parentCategoriesId'         => $this->parentCategoriesId,
            // 'parentCategoryIds'          => $this->parentCategoryIds
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

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
   
                $event->sheet->getDelegate()->freezePane('R4');
   
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,      
            'B' => 25,      
            // 'C' => 22,   
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

    // public function title(): string
    // {
    //     return $this->name;
    // }

}
