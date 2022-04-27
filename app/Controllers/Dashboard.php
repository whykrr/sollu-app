<?php

namespace App\Controllers;

use App\Models\InvoiceStockSalesModel;

class Dashboard extends BaseController
{
	public function index()
	{
		$data['sidebar_active'] = 'dashboard';

		// instace model
		$sales = new InvoiceStockSalesModel();

		$data['flash'] = [
			'today_sales' => $sales->getSales('daily', date('Y-m-d')),
			'today_income' => $sales->getIncome('daily', date('Y-m-d')),
			'monthly_income' => $sales->getIncome('monthlyAll', date('Y-m')),
		];
		return view('dashboard/dashboard', $data);
	}

	//--------------------------------------------------------------------

}
