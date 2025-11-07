<?php

namespace App\Admin\Controllers;

use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\StockRecord;
use App\Models\StockSubCategory;
use App\Models\User;
use App\Services\CacheService;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StockRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Stock Records (In/Out)';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StockRecord());

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Name');
            $u = Admin::user();

            // Use cached stock sub-categories
            $filter->equal('stock_sub_category_id', 'Stock Sub Category')
                ->select(CacheService::getStockSubCategories($u->company_id)->pluck('name', 'id'));

            $filter->equal('created_by_id', 'Created By')
                ->select(User::where([
                    'company_id' => $u->company_id
                ])->pluck('name', 'id'));

            // Use cached stock categories
            $filter->equal('stock_category_id', 'Stock Category')
                ->select(CacheService::getStockCategories($u->company_id)->pluck('name', 'id'));
        });

        $grid->quickSearch('name');
        $u = Admin::user();

        $grid->disableBatchActions();
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable();

        $grid->column('created_at', __('Date'))
            ->display(function ($created_at) {
                return date('Y-m-d', strtotime($created_at));
            })->sortable();
        $grid->column('stock_item_id', __('Stock Item'))
            ->display(function ($stock_item_id) {
                $stock_item = StockItem::find($stock_item_id);
                if ($stock_item) {
                    return $stock_item->name;
                } else {
                    return '';
                }
            })->sortable();

        $grid->column('stock_category_id', __('Stock Category'))
            ->display(function ($stock_category_id) {
                $stock_category = StockCategory::find($stock_category_id);
                if ($stock_category) {
                    return $stock_category->name_text;
                } else {
                    return 'N/A';
                }
            })->sortable()
            ->hide();
        $grid->column('stock_sub_category_id', __('Stock Sub Category'))
            ->display(function ($stock_sub_category_id) {
                $stock_sub_category = StockSubCategory::find($stock_sub_category_id);
                if ($stock_sub_category) {
                    return $stock_sub_category->name_text;
                } else {
                    return 'N/A';
                }
            })->sortable();

        $grid->column('created_by_id', __('Created'))
            ->display(function ($created_by_id) {
                $user = User::find($created_by_id);
                if ($user) {
                    return $user->name;
                } else {
                    return 'N/A';
                }
            })->sortable();

        $grid->column('sku', __('Sku'))->hide();
        $grid->column('description', __('Description'))->hide();
        $grid->column('type', __('Type'))
            ->dot([
                'Sale' => 'success',
                'Stock In' => 'info',
                'Adjustment' => 'primary',
                'Damage' => 'danger',
                'Expired' => 'warning',
                'Lost' => 'danger',
                'Internal Use' => 'primary',
                'Other' => 'default'
            ])->sortable()
            ->filter([
                'Sale' => 'Sale',
                'Stock In' => 'Stock In',
                'Adjustment' => 'Adjustment',
                'Damage' => 'Damage',
                'Expired' => 'Expired',
                'Lost' => 'Lost',
                'Internal Use' => 'Internal Use',
                'Other' => 'Other'
            ]);
        $grid->column('quantity', __('Quantity'))->display(function ($quantity) {
            return number_format($quantity) . " " . $this->measurement_unit;
        })->sortable()
            ->totalRow(function ($amount) {
                return "<span class='text-danger'>" . number_format($amount) . "</span>";
            });
        $grid->column('selling_price', __('Selling Price'))->display(function ($selling_price) {
            return number_format($selling_price);
        })->sortable();
        $grid->column('total_sales', __('Total Sales'))->display(function ($total_sales) {
            return number_format($total_sales);
        })->sortable()
            ->totalRow(function ($amount) {
                return "<span class='text-danger'>" . number_format($amount) . "</span>";
            });

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
        $show = new Show(StockRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_id', __('Company id'));
        $show->field('stock_item_id', __('Stock item id'));
        $show->field('stock_category_id', __('Stock category id'));
        $show->field('stock_sub_category_id', __('Stock sub category id'));
        $show->field('created_by_id', __('Created by id'));
        $show->field('sku', __('Sku'));
        $show->field('name', __('Name'));
        $show->field('measurement_unit', __('Measurement unit'));
        $show->field('description', __('Description'));
        $show->field('type', __('Type'));
        $show->field('quantity', __('Quantity'));
        $show->field('selling_price', __('Selling price'));
        $show->field('total_sales', __('Total sales'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StockRecord());

        $u = Admin::user();
        $form->hidden('company_id')->default($u->company_id);

        $sub_items_ajax_url = url('api/stock-items') . '?company_id=' . $u->company_id;
        $form->select('stock_item_id', __('Stock Item'))
            ->ajax($sub_items_ajax_url)
            ->options(function ($id) {
                $sub_cat = StockItem::find($id);
                if ($sub_cat) {
                    return [
                        $sub_cat->id => $sub_cat->name
                    ];
                } else {
                    return [];
                }
            })->rules('required');


        $form->hidden('created_by_id', __('Created by id'))->default($u->id);
        
        $form->date('date', __('Transaction Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
            
        $form->radio('type', __('Transaction Type'))
            ->options(
                [
                    'Sale' => 'Sale (Stock Out - Revenue)',
                    'Stock In' => 'Stock In (Add Inventory - Purchase)',
                    'Adjustment' => 'Adjustment (Add/Correct Inventory)',
                    'Damage' => 'Damage (Stock Out - Loss)',
                    'Expired' => 'Expired (Stock Out - Loss)',
                    'Lost' => 'Lost (Stock Out - Loss)',
                    'Internal Use' => 'Internal Use (Stock Out)',
                    'Other' => 'Other (Stock Out)'
                ]
            )
            ->rules('required')
            ->help('Sale = Generate revenue | Stock In = Purchase more inventory | Damage/Expired/Lost = Record losses');
            
        $form->decimal('quantity', __('Quantity (Units)'))
            ->rules('required|min:0.01')
            ->help('Enter the quantity to add (Stock In) or remove (Stock Out)');
            
        $form->textarea('description', __('Description/Notes'))
            ->help('Optional: Add notes about this transaction');

        return $form;
    }
}
