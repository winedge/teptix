<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Banner;
use Exception;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AppHelper extends Controller

{
    public function deleteFile($fileName)
    {
        if ($fileName != "default.jpg") {
            $filePath = "images/upload/" . $fileName;
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    return true;
                } else {
                   return false;
                }
            } else {
                return false;
            }
            
        }
    }
    public function saveImage($uploadedFile)
    {
        $name = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        $destinationPath = public_path('/images/upload');
        $uploadedFile->move($destinationPath, $name);
        return $name;
    }
  
    public function saveImage1($uploadedFile)
    {
        $name = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        $destinationPath = public_path('/images/upload');
        $uploadedFile->move($destinationPath, $name);
        return $name;
    }
    
    public function saveUploadedFile($uploadedFile)
    {
        $name = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        $destinationPath = public_path('/images/upload');
        $uploadedFile->move($destinationPath, $name);
        return $name;
    }

     public function saveApiImage($request)
    {
        if (!empty($request->image)) {
            $img = $request->image;
            $img = str_replace('data:image/png;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $img_code = base64_decode($img);
            $Iname = uniqid();
            $file = public_path('/images/upload/') . $Iname . ".png";
            file_put_contents($file, $img_code);
            return $Iname . ".png";
        }
        throw new \InvalidArgumentException('No image data provided');
    }

    /**
     * Save only the 'image_2' field from the request as a PNG file and return its filename.
     */
    public function saveApiImage1($request)
    {
        if (!empty($request->image_2)) {
            $img2 = $request->image_2;
            $img2 = str_replace('data:image/png;base64,', '', $img2);
            $img2 = str_replace(' ', '+', $img2);
            $img_code2 = base64_decode($img2);
            $Iname2 = uniqid();
            $file2 = public_path('/images/upload/') . $Iname2 . ".png";
            file_put_contents($file2, $img_code2);
            return $Iname2 . ".png";
        }
        throw new \InvalidArgumentException('No image_2 data provided');
    }

    public function saveEnv($envData)
    {
        $envFile = base_path('.env');
        if ($envFile) {
            $str = file_get_contents($envFile);
            if (count($envData) > 0) {
                foreach ($envData as $envKey => $envValue) {
                    $keyPosition = strpos($str, "{$envKey}=");
                    $endOfLinePosition = strpos($str, "\n", $keyPosition);
                    $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                    if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                        $str .= "{$envKey}={$envValue}\n";
                    } else {
                        $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                    }
                }
            }
            $str = substr($str, 0, -1);
            try {
                if (file_put_contents($envFile, $str)) {
                    return true;
                }
            } catch (Exception $e) {
                Log::info($e->getMessage());
                return redirect()->route('admin-setting')->with('Exception', $e->getMessage());
            }
        }
    }

    public function mailConfig()
    {
        $setting = Setting::first();
        if ($setting->mail_notification) {
            Config::set('mail.default', $setting->mail_mailer);
            Config::set('mail.mailers.smtp.host', $setting->mail_host);
            Config::set('mail.mailers.smtp.port', $setting->mail_port);
            Config::set('mail.mailers.smtp.username', $setting->mail_username);
            Config::set('mail.mailers.smtp.password', $setting->mail_password);
            Config::set('mail.mailers.smtp.encryption', $setting->mail_encryption);
            Config::set('mail.from',  ['address' => $setting->sender_email, 'name' => $setting->app_name]);
        }
        return true;
    }

    public function sendOneSignal($for, $device_token, $message)
    {
        $setting = Setting::first();
        if ($for == 'organizer')
            $app_id = $setting->or_onesignal_app_id;
        else
            $app_id = $setting->onesignal_app_id;

        try {
            $content1 = array("en" => $message);
            $fields1 = array(
                'app_id' => $app_id,
                'include_player_ids' => array($device_token),
                'data' => null,
                'contents' => $content1
            );
            $fields1 = json_encode($fields1);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $th) {
        }
        return true;
    }

    public function eventStatusChange()
    {
        $timezone = Setting::find(1)->timezone ?? 'UTC';
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s');

        Order::with('event')->whereOrderStatus('Pending')->whereHas('event', function ($q) use ($now) {
            $q->where('end_time', '<=', $now);
        })->get()->each->update(['order_status' => 'Complete']);

        // Automatically disable banners (status = 0 / inactive) when event end_time has passed
        Banner::where('status', 1)->whereHas('event', function ($q) use ($now) {
            $q->where('end_time', '<=', $now);
        })->update(['status' => 0]);

        return true;
    }
}
