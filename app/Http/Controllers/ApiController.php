<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Utils;
use Dflydev\DotAccessData\Util;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class ApiController extends BaseController
{

    public function login(Request $r)
    {
        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if password is provided
        if ($r->password == null) {
            Utils::error("Password is required.");
        }

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


    public function register(Request $r)
    {

        if ($r->first_name == null) {
            Utils::error("First name is required.");
        }
        //check if last name is provided
        if ($r->last_name == null) {
            Utils::error("Last name is required.");
        }
        //check if email is provided
        if ($r->email == null) {
            Utils::error("Email is required.");
        }
        //check if email is valid
        if (!filter_var($r->email, FILTER_VALIDATE_EMAIL)) {
            Utils::error("Email is invalid.");
        }

        //check if email is already registered
        $u = User::where('email', $r->email)->first();
        if ($u != null) {
            Utils::error("Email is already registered.");
        }
        //check if password is provided
        if ($r->password == null) {
            Utils::error("Password is required.");
        }

        //check if company name is provided
        if ($r->company_name == null) {
            Utils::error("Company name is required.");
        }
        if ($r->currency == null) {
            Utils::error("Currency is required.");
        }

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

        //DB instert into admin_role_users
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
