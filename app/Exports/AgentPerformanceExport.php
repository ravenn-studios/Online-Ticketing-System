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

// class AgentPerformanceExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
class AgentPerformanceExport implements FromView, WithStyles, ShouldAutoSize
{

    protected $user;
    protected $ticketsData;

    // public function __construct(array $users)
    public function __construct($user, $ticketsData)
    {
		$this->user      = $user;
		$this->ticketsData = $ticketsData;
    }

    public function view(): View
    {
        return view('exports.users', [
			'user'    => $this->user,
			'tickets' => $this->ticketsData,
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



    /*public function columnWidths(): array
    {
        return [
            'A' => 25,       
            'B' => 40,       
        ];
    }
*/
    /*public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(40);
                // $event->sheet->getDelegate()->getStyle('R37:Z37')->getAlignment()->setWrapText(true);
            },
        ];
    }*/

}
