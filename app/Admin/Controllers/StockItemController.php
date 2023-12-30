<?php

namespace App\Admin\Controllers;

use App\Models\StockItem;
use App\Models\StockSubCategory;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StockItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Stock Items';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StockItem());

        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('company_id', __('Company id'));
        $grid->column('created_by_id', __('Created by id'));
        $grid->column('stock_category_id', __('Stock category id'));
        $grid->column('stock_sub_category_id', __('Stock sub category id'));
        $grid->column('financial_period_id', __('Financial period id'));
        $grid->column('name', __('Name'));
        $grid->column('description', __('Description'));
        $grid->column('image', __('Image'));
        $grid->column('barcode', __('Barcode'));
        $grid->column('sku', __('Sku'));
        $grid->column('generate_sku', __('Generate sku'));
        $grid->column('update_sku', __('Update sku'));
        $grid->column('gallery', __('Gallery'));
        $grid->column('buying_price', __('Buying price'));
        $grid->column('selling_price', __('Selling price'));
        $grid->column('original_quantity', __('Original quantity'));
        $grid->column('current_quantity', __('Current quantity'));

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
        $show = new Show(StockItem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_id', __('Company id'));
        $show->field('created_by_id', __('Created by id'));
        $show->field('stock_category_id', __('Stock category id'));
        $show->field('stock_sub_category_id', __('Stock sub category id'));
        $show->field('financial_period_id', __('Financial period id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('image', __('Image'));
        $show->field('barcode', __('Barcode'));
        $show->field('sku', __('Sku'));
        $show->field('generate_sku', __('Generate sku'));
        $show->field('update_sku', __('Update sku'));
        $show->field('gallery', __('Gallery'));
        $show->field('buying_price', __('Buying price'));
        $show->field('selling_price', __('Selling price'));
        $show->field('original_quantity', __('Original quantity'));
        $show->field('current_quantity', __('Current quantity'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        $u = Admin::user();
        $fiancial_period = Utils::getActiveFinancialPeriod($u->company_id);
        if ($fiancial_period == null) {
            return admin_error('Please create a financial period first.');
        }

        $form = new Form(new StockItem());
        $form->hidden('company_id', __('Company id'))->default($u->company_id);
        $form->hidden('created_by_id', __('Created by id'))->default($u->id);
        $sub_cat_ajax_url = url('api/stock-sub-categories');
        $sub_cat_ajax_url = $sub_cat_ajax_url . '?company_id=' . $u->company_id;
        $form->select('stock_sub_category_id', __('Stock Category'))
            ->ajax($sub_cat_ajax_url)
            ->options(function ($id) {
                $sub_cat = StockSubCategory::find($id);
                if ($sub_cat) {
                    return [
                        $sub_cat->id => $sub_cat->name_text . " (" . $sub_cat->measurement_unit . ")"
                    ];
                } else {
                    return [];
                }
            })->rules('required');

        $form->text('name', __('Name'))->rules('required');
        $form->image('image', __('Image'))
            ->uniqueName();


        $form->text('sku', __('SKU'));




        if ($form->isEditing()) {
            $form->radio('update_sku', __('Update SKU'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])->when('Yes', function (Form $form) {
                    $form->text('sku', __('ENTER SKU (Batch Number)'))->rules('required');
                })->rules('required')
                ->default('No');
        } else {
            $form->hidden('update_sku', __('Update SKU'))->default('No');
            $form->radio('generate_sku', __('Generate SKU (Batch Number)'))
                ->options(
                    [
                        'Manual' => 'Manual',
                        'Auto' => 'Auto',
                    ]
                )->when('Manual', function (Form $form) {
                    $form->text('sku', __('ENTER SKU (Batch Number)'))->rules('required');
                })->rules('required');
        }


        $form->multipleImage('gallery', __('Item Gallery'))
            ->removable()
            ->uniqueName()
            ->downloadable();

        $form->decimal('buying_price', __('Buying Price'))
            ->default(0.00)
            ->rules('required');

        $form->decimal('selling_price', __('Selling Price'))
            ->default(0.00)
            ->rules('required');

        $form->decimal('original_quantity', __('Original quantity (in units)'))
            ->default(0.00)
            ->rules('required');
        $form->textarea('description', __('Description'));

        return $form;
    }
}
