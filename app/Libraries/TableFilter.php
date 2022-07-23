<?php

namespace App\Libraries;

class TableFilter
{
    private $type;
    private $filterType = [
        'monthly' => 'Bulanan',
        'daily' => 'Harian',
        'range' => 'Jangka Waktu'
    ];
    private $reset;
    private $year;
    private $month;

    public function __construct($type = 'basic')
    {
        helper('general');
        $this->type = $type;
        $this->month = getMonthIndo();
    }

    public function setYear(array $year)
    {
        $this->year = $year;
        return $this;
    }

    public function setReset(string $url)
    {
        $this->reset = $url;
        return $this;
    }

    public function getFilter(string $action, array $filter,  $export_button = false)
    {
        // set default when empty
        $filters = [
            'type_filter' => @$filter['type_filter'] ?: 'monthly',
            'month' => @$filter['month'] ?: date('m'),
            'year' => @$filter['year'] ?: date('Y'),
            'start_date' => @$filter['start_date'] ?: date('Y-m-d'),
            'end_date' => @$filter['end_date'] ?: date('Y-m-d'),
            'date' => @$filter['date'] ?: date('Y-m-d')
        ];

        return view('filters/' . $this->type, [
            'types' => $this->filterType,
            'action' => $action,
            'reset' => $this->reset,
            'filter' => $filters,
            'years' => $this->year,
            'months' => $this->month,
            'export_button' => $export_button,
        ]);
    }
}
