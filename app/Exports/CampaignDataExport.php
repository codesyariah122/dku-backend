<?php
namespace App\Exports;

use App\Models\Campaign;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithMapping;

class CampaignDataExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    use Exportable;

    public function query()
    {
        return Campaign::query();
    }

    public function map($campaign): array
    {
        return [
            $campaign->id,
            $campaign->title,
            $campaign->slug,
            $campaign->views,
            $campaign->author,
            $campaign->publish ? 'Yes' : 'No', // Contoh pemetaan nilai boolean
            $campaign->donation_target,
            $campaign->total_transfer,
            $campaign->created_at instanceof \DateTime ? $campaign->created_at->format('Y-m-d') : $campaign->created_at, // Contoh pemetaan format tanggal
            $campaign->end_campaign instanceof \DateTime ? $campaign->end_campaign->format('Y-m-d') : $campaign->end_campaign, // Contoh pemetaan tanggal opsional
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Slug',
            'Views',
            'Author',
            'Publish',
            'Donation Target',
            'Total Transfer',
            'Created At',
            'End Campaign'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}