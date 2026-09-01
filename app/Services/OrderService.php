<?php
namespace App\Services;

use App\Models\AppUser;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

class OrderService{
    
    public function orderCreate($requestData){
        try{
            $userData=$this->getUserData($requestData);
            $eventData=$this->getEventData($requestData['eventId']);
            dd($eventData);
        } catch (\Exception $exception) {
            Log::error("Error in " . __CLASS__ . "@" . __METHOD__ . ": " . $exception->getMessage());
        }
    }

    public function getUserData($requestData){
        try{
            $user = AppUser::firstOrCreate(
                ['email' => $requestData['email']],
                [
                    'password' => bcrypt('123456'),
                    'name' => $requestData['name'] ?? time(),
                    'provider' => 'LOCAL',
                    'is_verify' => 1,
                ]
            );
            return $user;
        } catch (\Exception $exception) {
            Log::error("Error in " . __CLASS__ . "@" . __METHOD__ . ": " . $exception->getMessage());
        }
    }

    private function getEventData($eventId){
        try{
            return Event::join('users', 'events.user_id', '=', 'users.id')
            ->where('events.id', $eventId)
            ->select('first_name','last_name','users.id as organization_id')
            ->first();
        } catch (\Exception $exception) {
            Log::error("Error in " . __CLASS__ . "@" . __METHOD__ . ": " . $exception->getMessage());
        }
    }

    // private function addOrderData($requestData){
    //     try{
    //         $orderData=array(
    //         'order_id'=>'#' . rand(9999, 100000),
    //         'customer_id'=>$requestData['userId'],
    //         'organization_id'=>$requestData['organization_id'],
    //         'event_id'=>$requestData['eventId'],
    //         'ticket_id'=>$requestData['ticketId'],
    //         'quantity'=>array_sum(explode(',', $data['quantity'])),
    //         'ticket_date'=>Carbon::parse($data['ticket_date'])->format('Y-m-d 00:00:00'),
    //         'tax'=>,
    //         'org_commission'=>,
    //         'payment'=>,
    //         'payment_type'=>'LOCAL',
    //         'payment_status'=>,
    //         'payment_token'=>,
    //         'order_status'=>,
    //         'org_pay_status'=>,
    //         'admin_revenue'=>,
    //         'org_revenue'=>
    //     );
    //     } catch (\Exception $exception) {
    //         Log::error("Error in " . __CLASS__ . "@" . __METHOD__ . ": " . $exception->getMessage());
    //     }
    // }
}
?>
