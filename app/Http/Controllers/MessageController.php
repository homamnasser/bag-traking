<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public static function createAccountRequestMessage(array $data)
    {
        $message= Message::create([
            'type' => 'account_creation',
            'sender_id' => $data['sender_id'],
            'receiver_id'=> 1,
            'data' => [
                'message' => "{$data['role']} asked to create account in application",
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
            ]

        ]);
        $pushController = new PushNotificationController();
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            if ($admin->fcm_token) {
                $pushController->send(
                    [
                        'id' => $admin->id,
                        'fcm_token' => $admin->fcm_token,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                    ],
                    'New Account Request',
                    "{$data['first_name']} {$data['last_name']}  has requested to create an account.",
                     'messages/requests'
                );
            }
        }
        return $message;
    }

    public static function passwordResetRequestMessage(array $data)
    {
        $message = Message::create([
            'type' => 'account_update',
            'sender_id' => $data['sender_id'],
            'receiver_id' => 1,
            'data' => [
                'message' => "{$data['role']} had forgotten his password . asked to change it in the application",
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'new_password' => $data['new_password'],
            ]

        ]);
        $pushController = new PushNotificationController();

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            if ($admin->fcm_token) {
                $pushController->send(
                    [
                        'id' => $admin->id,
                        'fcm_token' => $admin->fcm_token,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                    ],
                    'New Account Request',
                    "{$data['full_name']}  has requested to change his password.",
                    'messages/requests'
                );
            }
        }
        return $message;
    }

    public function respondRequest(Request $request){

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:approved,rejected',
            'message_id'=>'required|exists:messages,id'
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
        $message = Message::find($request->message_id);
        $user = User::find($message->sender_id);

        if($message->status!='pending'){
            return response()->json([
                'code' => 422,
                'message' => 'This message has already been responded to',
            ],422);
        }

         if($request->action ==='approved'){

             if($message->type==='account_creation'){

                 $user->update(['is_active' => true]);


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

             }
             $message->update([
                 'status' => $request->action
             ]);

             return response()->json([
                 'code' => 200,
                 'message' => $request->action === 'approved'
                     ? 'The request has been approved successfully.'
                     : 'The request has been rejected successfully.',
             ], 200);

         } else {
             $message->update(['status'=>'rejected']);
             return response()->json([
                 'code' => 200,
                 'message' => 'The request was rejected successfully.',
             ],200);
         }
    }


    public function sendMessage(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'data' => 'required|string'
        ], [
            'data.required' => 'cannot send an empty message'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => $validator->errors()->first(),
            ],422);
        }

        Message::create([
            'sender_id' => auth()->id(),
            'receiver_id'=>1,
            'data' => $request->data,
            'type' => 'issue',
            'status' => 'approved'

        ]);
        $user = User::find(auth()->id());
        $role = $user->getRoleNames()->first();
        $pushController = new PushNotificationController();
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            if ($admin->fcm_token) {
                $pushController->send(
                    [
                        'id' => $admin->id,
                        'fcm_token' => $admin->fcm_token,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                    ],
                    'New Issue Reported',
                    "{$role} {$user->first_name} {$user->last_name} has reported an issue:\n\"{$request->data}\"",
                    'messages/requests'
                );
            }
        }

        return response()->json([
            'code' => 201,
            'message' => "the message has been send successfully."
        ],201);

    }

    public function getAllMessages()
    {
        $messages = Message::with('sender')
            ->where('receiver_id',1)
            ->get();


        $dataMessages = $messages->map(function ($message) {
            return [
                'userName' => $message->sender ? $message->sender->first_name . ' ' . $message->sender->last_name : null,
                'data' => $message->data,
            ];
        });
        return response()->json([
            'code' => 200,
            'data' => $dataMessages
        ],200);
    }

    public function getMessage($id)
    {
        $message=Message::find($id);

        if (!$message) {
            return response()->json([
                'code'=>404,
                'message' => 'message not found',
            ],404);
        }
        if($message->receiver_id!==1){
            return response()->json([
                'code'=>422,
                'message' => 'can not show this message',
            ],422);
        }

        $user=User::find($message->sender_id);
       $message=[
           'userName'=>$user->first_name . ' ' . $user->last_name,
           'data'=>$message->data
          ];
        return response()->json([
          'code'=>200,
          'data' => $message,
           ],200);
    }

    public function getMessageByType($type)
    {
        $message = Message::with('sender');

        if ($type === 'issue') {
            $message->where('type', 'issue');
        } elseif ($type === 'accountsRequest') {
            $message->whereIn('type', ['account_creation', 'account_update']);
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'the type should be issue or accountsRequest'
            ],400);
        }
        $messages = $message->get();

        $dataMessages = $messages->map(function ($message) {
            return [
                'userName' => $message->sender ? $message->sender->first_name . ' ' . $message->sender->last_name : null,
                'data' => $message->data,
            ];
        });

        return response()->json([
            'code' => 200,
            'data' => $dataMessages
        ],200);

    }

    public function getCustomerNotification()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthenticated. Please log in.'
            ], 401);
        }
        $messages = Message::where('receiver_id', $user->id)
            ->with('sender')
            ->get();
        if ($messages->isEmpty()) {
            return response()->json([
                'code' => 200,
                'message' => 'There is no message yet.',
                'data' => []
            ], 200);
        }
        $dataMessages = $messages->map(function ($message) {
            return $message->data;
        });

        return response()->json([
            'code' => 200,
            'message' => 'This is your messages',
            'data' => $dataMessages
        ], 200);
    }

}
