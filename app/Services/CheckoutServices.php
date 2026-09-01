<?php
namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use Carbon\Carbon;

class CheckoutServices{

    public function getTicketPrice($requestData){
        try{
            $priceAmt=0;
            $ticketIds=explode(',',$requestData['ticket_id']);
            $getEventDetails=Ticket::select('id','price')->whereIn('id',$ticketIds)->get();
            if(isset($getEventDetails) && count($getEventDetails)>0)
            {
                foreach($getEventDetails as $getEventDetail)
                {
                    $data = json_decode($requestData['ticketqty'], true);
                    $priceAmt+=($getEventDetail->price*$data["ticket_qty".$getEventDetail->id]);
                }
                $totalAmount=$priceAmt+($requestData['tax']??0);
                if(isset($requestData['coupon_code']) && $requestData['coupon_code']!=0){
                    $coupon = Coupon::where('id',$requestData['coupon_code'])->first();
                    if ($coupon->discount_type == 0) {
                        $discount = $totalAmount * ($coupon->discount / 100);
                    } else {
                        $discount = $coupon->discount;
                    }
                    if ($discount > $coupon->maximum_discount) {
                        $discount = $coupon->maximum_discount;
                    }
                    $totalAmount = $totalAmount - $discount;
                }
                return round($totalAmount, 2);
            }
        } catch (\Exception $exception) {
            // Handle exceptions here
            Log::error("Error in CheckoutServices.getTicketPrice(): " . $exception->getMessage());
        }
    }
}
?>
