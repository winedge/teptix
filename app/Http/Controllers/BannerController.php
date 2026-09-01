<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BannerController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('banner_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        (new AppHelper)->eventStatusChange();
        $banner = Banner::orderBy('id', 'DESC')->get();

        return view('admin.banner.index', compact('banner'));
    }

    public function create()
    {
        abort_if(Gate::denies('banner_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events = Event::where([
            ['status', 1],
            ['is_deleted', 0],
            ['event_status', 'Pending'],
            ['end_time', '>', $date->format('Y-m-d H:i:s')]
        ])->orderBy('start_time', 'desc')->get();

        return view('admin.banner.create', compact('events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'bail|required',
            'banner_type' => 'bail|required|in:event,general',
            'event_id' => 'bail|required_if:banner_type,event',
            'redirect_url' => 'bail|required_if:banner_type,general|nullable|url|max:255',
            'display_order' => 'bail|nullable|integer',
            'image' => 'bail|required|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=1905,height=600',
            'image_for_mobile' => 'bail|nullable|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=450,height=500',
            'image_for_android' => 'bail|nullable|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=1905,height=893',
        ]);

        $data = $request->all();
        $data['event_id'] = $request->banner_type === 'event' ? $request->event_id : null;
        $data['redirect_url'] = $request->banner_type === 'general' ? $request->redirect_url : null;
        if ($request->hasFile('image')) {
            $data['image'] = (new AppHelper)->saveImage($request->file('image'));
        }
        if ($request->hasFile('image_for_mobile')) {
            $data['image_for_mobile'] = (new AppHelper)->saveUploadedFile($request->file('image_for_mobile'));
        }
        if ($request->hasFile('image_for_android')) {
            $data['image_for_android'] = (new AppHelper)->saveUploadedFile($request->file('image_for_android'));
        }

        $banner = Banner::create($data);
        $event = $request->banner_type === 'event' ? Event::find($request->event_id) : null;
        AdminActivityLog::record(
            AdminActivityLog::PROMOTION_CREATED,
            $banner,
            __('Promotion created'),
            __(':title banner promotion was created.', ['title' => $banner->title]),
            [
                'promotion_kind' => 'banner',
                'banner_id' => $banner->id,
                'banner_title' => $banner->title,
                'event_id' => $banner->event_id,
                'event_name' => $event ? $event->name : null,
                'status' => $banner->status,
            ],
            $request
        );

        return redirect()->route('banner.index')->withStatus(__('Banner has added successfully.'));
    }

    public function edit(Banner $banner)
    {
        abort_if(Gate::denies('banner_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events = Event::where([
            ['status', 1],
            ['is_deleted', 0],
            ['event_status', 'Pending'],
            ['end_time', '>', $date->format('Y-m-d H:i:s')]
        ])->orderBy('start_time', 'desc')->get();

        return view('admin.banner.edit', compact('banner', 'events'));
    }

    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => 'bail|required',
            'banner_type' => 'bail|required|in:event,general',
            'event_id' => 'bail|required_if:banner_type,event',
            'redirect_url' => 'bail|required_if:banner_type,general|nullable|url|max:255',
            'display_order' => 'bail|nullable|integer',
            'image' => 'bail|nullable|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=1905,height=600',
            'image_for_mobile' => 'bail|nullable|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=450,height=500',
            'image_for_android' => 'bail|nullable|image|mimes:jpeg,png,jpg,gif|max:3048|dimensions:width=1905,height=893',
        ]);

        $data = $request->all();
        $data['event_id'] = $request->banner_type === 'event' ? $request->event_id : null;
        $data['redirect_url'] = $request->banner_type === 'general' ? $request->redirect_url : null;

        if ($request->hasFile('image')) {
            (new AppHelper)->deleteFile($banner->image);
            $data['image'] = (new AppHelper)->saveImage($request->file('image'));
        }

        if ($request->hasFile('image_for_mobile')) {
            if ($banner->image_for_mobile) {
                (new AppHelper)->deleteFile($banner->image_for_mobile);
            }
            $data['image_for_mobile'] = (new AppHelper)->saveUploadedFile($request->file('image_for_mobile'));
        }

        if ($request->hasFile('image_for_android')) {
            if ($banner->image_for_android) {
                (new AppHelper)->deleteFile($banner->image_for_android);
            }
            $data['image_for_android'] = (new AppHelper)->saveUploadedFile($request->file('image_for_android'));
        }

        Banner::find($banner->id)->update($data);

        return redirect()->route('banner.index')->withStatus(__('Banner has updated successfully.'));
    }

    public function destroy(Banner $banner)
    {
        try {
            (new AppHelper)->deleteFile($banner->image);
            if ($banner->image_for_mobile) {
                (new AppHelper)->deleteFile($banner->image_for_mobile);
            }
            if ($banner->image_for_android) {
                (new AppHelper)->deleteFile($banner->image_for_android);
            }
            $banner->delete();

            return true;
        } catch (Throwable $th) {
            return response('Data is Connected with other Data', 400);
        }
    }
}
