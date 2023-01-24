<?php

namespace App\Http\Controllers;

use carbon\carbon;
use App\Models\User;
use Illuminate\support\Str;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\PasswordReset as ModelsPasswordReset;

class PasswordResetController extends Controller
{
    public function send_reset_password_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $email = $request->email;
        //check User's Email Exists or not
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response([
                'message' => 'Email Doesnt exist',
                'status' => 'failed'
            ], 404);
        }
        //Generate Token
        $token = Str::random(60);
        //saving Data To Password Reset table
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
        // dump("http://127.0.0.1:3000/api/reset/" . $token);
        //sending Email with password reset view
        Mail::send('reset', ['token' => $token], function (message $message) use ($email) {
            $message->subject('Reset Your Password');
            $message->to($email);
        });
        return response([
            'message' => 'password reset email sent... check your Email.',
            'status' => 'success',
        ], 200);
    }
    public function reset(Request $request, $token)
    {
        //Delete token older more than 1 minute
        $formatted = Carbon::now()
            ->subMinutes(2)->toDateTimeString();
        PasswordReset::where('created_at', '<=', $formatted)
            ->delete();
        $request->validate([
            'password' => 'required|confirmed',
        ]);
        $passwordreset = PasswordReset::where('token', $token)
            ->first();

        if (!$passwordreset) {
            return response([
                'message' => 'Token is invalid or Expired',
                'status' => 'failed'
            ], 404);
        }
        $user = User::where('email', $passwordreset->email)
            ->first();
        $user->password = Hash::make($request->password);
        $user->save();
        //delete the token after reseting password
        PasswordReset::where('email', $user->email)->delete();
        return response([
            'message' => 'password Reset Success',
            'status' => 'success'
        ], 200);
    }
}
