<?php

namespace App\Admin\Controllers;

use App\Models\FinancialReport;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FinancialReportController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'FinancialReport';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        /* $rep = FinancialReport::find(1);
        FinancialReport::prepare($rep);
        die(); */
        $grid = new Grid(new FinancialReport());

        $grid->column('id', __('Id'))
            ->display(function ($id) {
                $url = url("/financial-report?id=$id");
                return "<a target='_blank' href='$url'>$id</a>";
            });
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('company_id', __('Company id'));
        $grid->column('user_id', __('User id'));
        $grid->column('type', __('Type'));
        $grid->column('period_type', __('Period type'));
        $grid->column('start_date', __('Start date'));
        $grid->column('end_date', __('End date'));
        $grid->column('currency', __('Currency'));
        $grid->column('file_generated', __('File generated'));
        $grid->column('file', __('File'));
        $grid->column('total_income', __('Total income'));
        $grid->column('total_expense', __('Total expense'));
        $grid->column('profit', __('Profit'));
        $grid->column('include_finance_accounts', __('Include finance accounts'));
        $grid->column('include_finance_records', __('Include finance records'));
        $grid->column('inventory_total_buying_price', __('Inventory total buying price'));
        $grid->column('inventory_total_selling_price', __('Inventory total selling price'));
        $grid->column('inventory_total_expected_profit', __('Inventory total expected profit'));
        $grid->column('inventory_total_earned_profit', __('Inventory total earned profit'));
        $grid->column('inventory_include_categories', __('Inventory include categories'));
        $grid->column('inventory_include_sub_categories', __('Inventory include sub categories'));
        $grid->column('inventory_include_products', __('Inventory include products'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(FinancialReport::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_id', __('Company id'));
        $show->field('user_id', __('User id'));
        $show->field('type', __('Type'));
        $show->field('period_type', __('Period type'));
        $show->field('start_date', __('Start date'));
        $show->field('end_date', __('End date'));
        $show->field('currency', __('Currency'));
        $show->field('file_generated', __('File generated'));
        $show->field('file', __('File'));
        $show->field('total_income', __('Total income'));
        $show->field('total_expense', __('Total expense'));
        $show->field('profit', __('Profit'));
        $show->field('include_finance_accounts', __('Include finance accounts'));
        $show->field('include_finance_records', __('Include finance records'));
        $show->field('inventory_total_buying_price', __('Inventory total buying price'));
        $show->field('inventory_total_selling_price', __('Inventory total selling price'));
        $show->field('inventory_total_expected_profit', __('Inventory total expected profit'));
        $show->field('inventory_total_earned_profit', __('Inventory total earned profit'));
        $show->field('inventory_include_categories', __('Inventory include categories'));
        $show->field('inventory_include_sub_categories', __('Inventory include sub categories'));
        $show->field('inventory_include_products', __('Inventory include products'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FinancialReport());

        $u = auth('admin')->user();
        $form->hidden('user_id')->value($u->id);
        $form->hidden('company_id')->value($u->company_id);
        $form->radio('type', __('Type'))
            ->options([
                'Financial' => 'Financial',
                'Inventory' => 'Inventory',
            ])->rules('required')
            ->when('Financial', function (Form $form) {
                $form->radio('include_finance_accounts', __('Include finance accounts'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])->rules('required');
                //include_finance_records
                $form->radio('include_finance_records', __('Include finance records'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])->rules('required');
            })->when('Inventory', function (Form $form) {
                $form->radio('inventory_include_categories', __('Inventory include categories'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])->rules('required');
                //inventory_include_sub_categories
                $form->radio('inventory_include_sub_categories', __('Inventory include sub categories'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])->rules('required');
                //inventory_include_products
                $form->radio('inventory_include_products', __('Inventory include products'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])->rules('required');
            })->rules('required');
        //period_type
        $form->radio('period_type', __('Period type'))
            ->options([
                'Today' => 'Today',
                'Yesterday' => 'Yesterday',
                'Week' => 'This week',
                'Month' => 'This month',
                'Cycle' => 'This financial year',
                'Year' => 'This year',
                'Custom' => 'Custom',
            ])
            ->when('Custom', function (Form $form) {
                $form->dateRange('start_date', 'end_date', 'Start date - End date')->rules();
            })->rules('required');

        return $form;
    }
}
