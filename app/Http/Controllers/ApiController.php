<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetItemRequest;
use App\Http\Requests\ContributionRecordRequest;
use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\GenericModelRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\BudgetItem;
use App\Models\Company;
use App\Models\ContributionRecord;
use App\Models\StockSubCategory;
use App\Models\User;
use App\Models\Utils;
use App\Services\CacheService;
use Dflydev\DotAccessData\Util;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiController extends BaseController
{


    public function file_uploading(FileUploadRequest $r)
    {
        $path = Utils::file_upload($r->file('photo'));
        if ($path == '') {
            Utils::error("File not uploaded.");
        }
        Utils::success([
            'file_name' => $path,
        ], "File uploaded successfully.");
    }

    public function manifest(Request $r)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $roles = DB::table('admin_role_users')->where('user_id', $u->id)->get();
        
        // Use cached company settings
        $company = CacheService::getCompanySettings($u->company_id);
        
        $data = [
            'name' => 'Invetor-Track',
            'short_name' => 'IT',
            'description' => 'Inventory Management System',
            'version' => '1.0.0',
            'author' => 'M. Muhido',
            'user' => $u,
            'roles' => $roles,
            'company' => $company,
        ];
        Utils::success($data, "Success.");
    }

    public function my_list(Request $r, $model)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $model = "App\Models\\" . $model;
        $data = $model::where('company_id', $u->company_id)->limit(100000)->get();
        Utils::success($data, "Listed successfully.");
    }





    public function budget_item_create(BudgetItemRequest $r)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $model = BudgetItem::class;
        $object = BudgetItem::find($r->get('id'));
        $isEdit = true;
        if ($object == null) {
            $object = new $model();
            $isEdit = false;
        }


        $table_name = $object->getTable();
        $columns = Schema::getColumnListing($table_name);
        $except = ['id', 'created_at', 'updated_at'];
        $data = $r->validated(); // Use validated data


        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                continue;
            }
            if (in_array($key, $except)) {
                continue;
            }
            if ($value == null) {
                continue;
            }
            if ($value == '') {
                continue;
            }
            $object->$key = $value;
        }
        $object->company_id = $u->company_id;

        try {
            $object->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }
        if ($object == null) {
            Utils::error("Failed to save.");
        }

        $new_object = $model::find($object->id);

        if ($isEdit) {
            Utils::success($new_object, "Updated successfully.");
        } else {
            Utils::success($new_object, "Created successfully.");
        }
    }





    public function contribution_records_create(ContributionRecordRequest $r)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }

        $treasurer = User::find($r->treasurer_id); // Already validated
        if ($treasurer == null) {
            Utils::error("Treasurer not found.");
        }

        $model = ContributionRecord::class;
        $object = ContributionRecord::find($r->get('id'));
        $isEdit = true;
        if ($object == null) {
            $object = new $model();
            $isEdit = false;
        }


        $table_name = $object->getTable();
        $columns = Schema::getColumnListing($table_name);
        $except = ['id', 'created_at', 'updated_at'];
        $data = $r->validated(); // Use validated data


        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                continue;
            }
            if (in_array($key, $except)) {
                continue;
            }
            if ($value == null) {
                continue;
            }
            if ($value == '') {
                continue;
            }
            $object->$key = $value;
        }
        $object->company_id = $u->company_id;
        $object->treasurer_id = $treasurer->id; //set treasurer_id

        try {
            $object->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }
        if ($object == null) {
            Utils::error("Failed to save.");
        }

        $new_object = $model::find($object->id);

        if ($isEdit) {
            Utils::success($new_object, "Updated successfully.");
        } else {
            Utils::success($new_object, "Created successfully.");
        }
    }





    public function my_update(GenericModelRequest $r, $model)
    {
        $u = Utils::get_user($r);
        if ($u == null) {
            Utils::error("Unauthonticated.");
        }
        $model = "App\Models\\" . $model;
        $object = $model::find($r->get('id'));
        $isEdit = true;
        if ($object == null) {
            $object = new $model();
            $isEdit = false;
        }


        $table_name = $object->getTable();
        $columns = Schema::getColumnListing($table_name);
        $except = ['id', 'created_at', 'updated_at'];
        $data = $r->validated(); // Use validated data

        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) {
                continue;
            }
            if (in_array($key, $except)) {
                continue;
            }
            if ($value == null) {
                continue;
            }
            if ($value == '') {
                continue;
            }
            $object->$key = $value;
        }
        $object->company_id = $u->company_id;


        //temp_image_field
        if ($r->temp_file_field != null) {
            if (strlen($r->temp_file_field) > 1) {
                $file  = $r->file('photo');
                if ($file != null) {
                    $path = "";
                    try {
                        $path = Utils::file_upload($r->file('photo'));
                    } catch (\Exception $e) {
                        $path = "";
                    }
                    if (strlen($path) > 3) {
                        $fiel_name = $r->temp_file_field;
                        $object->$fiel_name = $path;
                    }
                }
            }
        }

        try {
            $object->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }
        $new_object = $model::find($object->id);

        if ($isEdit) {
            Utils::success($new_object, "Updated successfully.");
        } else {
            Utils::success($new_object, "Created successfully.");
        }
    }




    public function login(LoginRequest $r)
    {
        $user = User::where('email', $r->email)->first();
        if ($user == null) {
            Utils::error("Account not found.");
        }

        if (!password_verify($r->password, $user->password)) {
            Utils::error("Invalid password.");
        }

        $company = Company::find($user->company_id);
        if ($company == null) {
            Utils::error("Company not found.");
        }

        Utils::success([
            'user' => $user,
            'company' => $company,
        ], "Login successful.");
    }


    public function register(RegisterRequest $r)
    {
        $new_user = new User();
        $new_user->first_name = $r->first_name;
        $new_user->last_name = $r->last_name;
        $new_user->username = $r->email;
        $new_user->email = $r->email;
        $new_user->password = password_hash($r->password, PASSWORD_DEFAULT);
        $new_user->name = $r->first_name . " " . $r->last_name;
        $new_user->phone_number = $r->phone_number;
        $new_user->company_id = 1;
        $new_user->status = "Active";

        try {
            $new_user->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }

        $registered_user = User::find($new_user->id);
        if ($registered_user == null) {
            Utils::error("Failed to register user.");
        }

        $company = new Company();
        $company->owner_id = $registered_user->id;
        $company->name = $r->company_name;
        $company->email = $r->email;
        $company->phone_number = $r->phone_number;
        $company->status = 'Active';
        $company->currency = $r->currency;
        $company->license_expire = date('Y-m-d', strtotime("+1 year"));

        try {
            $company->save();
        } catch (\Exception $e) {
            Utils::error($e->getMessage());
        }

        $registered_company = Company::find($company->id);
        if ($registered_company == null) {
            Utils::error("Failed to register company.");
        }

        //Update user's company_id
        $registered_user->company_id = $registered_company->id;
        $registered_user->save();

        //DB insert into admin_role_users
        DB::table('admin_role_users')->insert([
            'user_id' => $registered_user->id,
            'role_id' => 2,
        ]);

        Utils::success([
            'user' => $registered_user,
            'company' => $registered_company,
        ], "Registration successful.");
    }
}
