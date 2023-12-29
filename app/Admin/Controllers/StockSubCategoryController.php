<?php

namespace App\Admin\Controllers;

use App\Models\StockSubCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StockSubCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'StockSubCategory';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StockSubCategory());

        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('company_id', __('Company id'));
        $grid->column('stock_category_id', __('Stock category id'));
        $grid->column('name', __('Name'));
        $grid->column('description', __('Description'));
        $grid->column('status', __('Status'));
        $grid->column('image', __('Image'));
        $grid->column('buying_price', __('Buying price'));
        $grid->column('selling_price', __('Selling price'));
        $grid->column('expected_profit', __('Expected profit'));
        $grid->column('earned_profit', __('Earned profit'));
        $grid->column('measurement_unit', __('Measurement unit'));
        $grid->column('current_quantity', __('Current quantity'));
        $grid->column('reorder_level', __('Reorder level'));

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
        $show = new Show(StockSubCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_id', __('Company id'));
        $show->field('stock_category_id', __('Stock category id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('status', __('Status'));
        $show->field('image', __('Image'));
        $show->field('buying_price', __('Buying price'));
        $show->field('selling_price', __('Selling price'));
        $show->field('expected_profit', __('Expected profit'));
        $show->field('earned_profit', __('Earned profit'));
        $show->field('measurement_unit', __('Measurement unit'));
        $show->field('current_quantity', __('Current quantity'));
        $show->field('reorder_level', __('Reorder level'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StockSubCategory());

        $form->number('company_id', __('Company id'));
        $form->number('stock_category_id', __('Stock category id'));
        $form->textarea('name', __('Name'));
        $form->textarea('description', __('Description'));
        $form->text('status', __('Status'))->default('active');
        $form->textarea('image', __('Image'));
        $form->number('buying_price', __('Buying price'));
        $form->number('selling_price', __('Selling price'));
        $form->number('expected_profit', __('Expected profit'));
        $form->number('earned_profit', __('Earned profit'));
        $form->text('measurement_unit', __('Measurement unit'));
        $form->number('current_quantity', __('Current quantity'));
        $form->number('reorder_level', __('Reorder level'));

        return $form;
    }
}
