<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public static function createAccountRequestMessage(array $data)
    {
        return Message::create([
            'type' => 'account_creation',
            'subject' => "{$data['role']} asked to create account in application",
            'user_id' => $data['user_id'],
            'data' => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
            ]

        ]);
    }

    public static function passwordResetRequestMessage(array $data)
    {
        return Message::create([
            'type' => 'account_update',
            'subject' => "{$data['role']} had forgotten his password . asked to change it in the application",
            'user_id' => $data['user_id'],
            'data' => [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'new_password' => $data['new_password'],
            ]

        ]);

    }

    public function respondRequest(Request $request){

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:approved,rejected',
            'id_message'=>'required|exists:message,id'
        ], [
            'action.required' => 'You must select an action type ',
            'message.exists'=>'The message was not found '
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);
        }
        $message = Message::find($request->id_message);
        $user = User::find($message->user_id);

         if($request->action ==='approved'){

             if($message->type==='account_creation'){

                 $user->update(['is_active' => true]);

                 return response()->json([
                     'code' => 200,
                     'message' =>'Account creation request approved successfully.',
                 ],200);

             } elseif ($message->type === 'account_update') {
                 $newPassword = $message->data['new_password'];

                 if (!$newPassword) {
                     return response()->json([
                         'code' => 400,
                         'message' => 'New password not found in the request data.',
                     ],400);
                 }

                 $user->update([
                     'password' => Hash::make($newPassword),
                 ]);

                 return response()->json([
                     'code' => 200,
                     'message' => 'Password change request approved and updated successfully.',
                 ],200);
             }

         } else {
             return response()->json([
                 'code' => 200,
                 'message' => 'The request was rejected successfully.',
             ],200);
         }
    }





    public function sendMessage(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'subject' => 'string',
            'data' => 'required|string'
        ], [
            'data.required' => 'cannot send an empty message'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ]);
        }

        Message::create([
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'data' => $request->data,
            'type' => 'issue',
            'status' => 'approved'

        ]);

        return response()->json([
            'code' => 201,
            'message' => "the message has been send successfully."
        ],201);

    }

    public function getAllMessages()
    {
        $messages = Message::with('user')->get();


        $dataMessages = $messages->map(function ($message) {
            return [
                'userName' => $message->user ? $message->user->first_name . ' ' . $message->user->last_name : null,
                'subject' => $message->subject,
                'data' => $message->data,
            ];
        });
        return response()->json([
            'code' => 200,
            'data' => $dataMessages
        ]);
    }

    public function getMessage($id)
    {
        $message=Message::find($id);

        if (!$message) {
            return response()->json([
                'code'=>404,
                'message' => 'message not found',
            ]);
        }

        $user=User::find($message->user_id);
       $message=[
           'userName'=>$user->first_name . ' ' . $user->last_name,
           'subject'=>$message->subject,
           'data'=>$message->data
          ];
        return response()->json([
          'code'=>200,
          'data' => $message,
           ]);
    }

    public function getMessageByType($type)
    {
        $message = Message::with('user');

        if ($type === 'issue') {
            $message->where('type', 'issue');
        } elseif ($type === 'accountsRequest') {
            $message->whereIn('type', ['account_creation', 'account_update']);
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'the type should be issue or accountsRequest'
            ]);
        }
        $messages = $message->get();

        $dataMessages = $messages->map(function ($message) {
            return [
                'userName' => $message->user ? $message->user->first_name . ' ' . $message->user->last_name : null,
                'subject' => $message->subject,
                'data' => $message->data,
            ];
        });

        return response()->json([
            'code' => 200,
            'data' => $dataMessages
        ]);

    }
}
