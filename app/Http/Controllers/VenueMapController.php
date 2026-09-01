<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\EventVenueMap;
use App\Models\EventVenueRow;
use App\Models\EventVenueSection;
use App\Models\EventVenueSeat;
use App\Models\VenueMapRow;
use App\Models\VenueMapSeat;
use App\Models\VenueMapSection;
use App\Models\VenueMapTemplate;
use App\Models\VenueMapTemplateSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VenueMapController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('admin'), 403);
    }

    public function index()
    {
        $this->authorizeAdmin();

        $templates = VenueMapTemplate::with('venue')
            ->withCount([
                'seats',
                'seats as plotted_seats_count' => function ($query) {
                    $query->whereNotNull('x_percent')->whereNotNull('y_percent');
                },
                'eventVenueMaps as event_venue_maps_count',
            ])
            ->orderBy('id', 'DESC')
            ->get();

        return view('admin.venue_map.index', compact('templates'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('admin.venue_map.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'venue_name' => 'required|string|max:191',
            'location' => 'nullable|string|max:191',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'postal_code' => 'nullable|string|max:25',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'template_name' => 'nullable|string|max:191',
            'version' => 'required|string|max:50',
            'expected_seat_count' => 'required|integer|min:1',
            'background_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'numbering_mode' => 'nullable|string|in:section,continuous',
            'counting_direction' => 'nullable|string|in:left_to_right,right_to_left',
            'layout_style' => 'nullable|string|in:curved,straight,curved_rotated',
            'show_row_name' => 'nullable|boolean',
            'seat_shape' => 'nullable|string|in:circle,square,rectangle,arch,horseshoe',
            'focal_x' => 'nullable|numeric|between:0,100',
            'focal_y' => 'nullable|numeric|between:0,100',
        ], [], [
            'lat' => 'latitude',
            'lng' => 'longitude',
        ]);

        $imageInfo = getimagesize($request->file('background_image')->getPathname());
        $backgroundImage = (new AppHelper)->saveUploadedFile($request->file('background_image'));

        $template = DB::transaction(function () use ($request, $imageInfo, $backgroundImage) {
            $venue = Venue::create([
                'name' => $request->venue_name,
                'slug' => $this->uniqueVenueSlug($request->venue_name),
                'location' => $request->location,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'status' => Venue::STATUS_ACTIVE,
                'created_by' => Auth::id(),
            ]);

            $template = VenueMapTemplate::create([
                'venue_id' => $venue->id,
                'name' => $request->template_name,
                'version' => $request->version,
                'expected_seat_count' => $request->expected_seat_count,
                'background_image' => $backgroundImage,
                'background_width' => $imageInfo[0] ?? null,
                'background_height' => $imageInfo[1] ?? null,
                'status' => VenueMapTemplate::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            VenueMapTemplateSetting::create([
                'venue_map_template_id' => $template->id,
                'numbering_mode' => $request->input('numbering_mode', 'section'),
                'counting_direction' => $request->input('counting_direction', 'left_to_right'),
                'layout_style' => $request->input('layout_style', 'curved'),
                'show_row_name' => $request->has('show_row_name') ? 1 : 0,
                'seat_shape' => $request->input('seat_shape', 'circle'),
                'focal_x' => $request->input('focal_x', 50),
                'focal_y' => $request->input('focal_y', 10),
            ]);

            return $template;
        });

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Venue map template created. Add sections and position seats.'));
    }

    public function edit(VenueMapTemplate $template)
    {
        $this->authorizeAdmin();

        $template->load([
            'venue',
            'sections' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            },
            'sections.rows' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            },
        ]);

        $seats = $template->seats()
            ->with(['section', 'row'])
            ->orderBy('venue_map_section_id')
            ->orderBy('venue_map_row_id')
            ->orderBy('seat_number')
            ->get();

        $totalSeats = $seats->count();
        $plottedSeats = $seats->whereNotNull('x_percent')->whereNotNull('y_percent')->count();

        return view('admin.venue_map.edit', compact('template', 'seats', 'totalSeats', 'plottedSeats'));
    }

    public function storeSectionRow(Request $request, VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        if (! $request->has('rows')) {
            $request->merge([
                'rows' => [[
                    'section_name' => $request->section_name,
                    'section_code' => $request->section_code,
                    'row_color' => $request->row_color,
                    'row_name' => $request->row_name,
                    'seat_count' => $request->seat_count,
                ]],
            ]);
        }

        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.section_name' => 'required|string|max:191',
            'rows.*.section_code' => 'nullable|string|max:50',
            'rows.*.row_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
            'rows.*.row_name' => 'required|string|max:50',
            'rows.*.seat_count' => 'required|integer|min:1|max:1000',
        ]);

        $rows = collect($validated['rows'])->map(function ($row) {
            return [
                'section_name' => trim($row['section_name']),
                'section_code' => isset($row['section_code']) ? trim($row['section_code']) : null,
                'row_color' => $row['row_color'] ?? null,
                'row_name' => trim($row['row_name']),
                'seat_count' => (int) $row['seat_count'],
            ];
        })->values();

        $existingSeatCount = (int) VenueMapSeat::where('venue_map_template_id', $template->id)->count();
        $newSeatCount = (int) $rows->sum('seat_count');
        $expectedSeatCount = (int) $template->expected_seat_count;

        if (($existingSeatCount + $newSeatCount) > $expectedSeatCount) {
            return back()
                ->withInput()
                ->with('error', __('Seat count cannot exceed the expected seat count.'));
        }

        $seenRows = [];

        foreach ($rows as $rowData) {
            $rowKey = strtolower($rowData['section_name'] . '|' . $rowData['row_name']);

            if (isset($seenRows[$rowKey])) {
                return back()
                    ->withInput()
                    ->with('error', __('The same section and row was added more than once.'));
            }

            $seenRows[$rowKey] = true;

            $existingRow = VenueMapRow::whereHas('section', function ($query) use ($rowData, $template) {
                $query->where('venue_map_template_id', $template->id)
                    ->where('name', $rowData['section_name']);
            })->where('name', $rowData['row_name'])->exists();

            if ($existingRow) {
                return back()
                    ->withInput()
                    ->with('error', __('This row already exists in the selected section.'));
            }
        }

        DB::transaction(function () use ($rows, $template) {
            foreach ($rows as $rowData) {
                $section = VenueMapSection::firstOrCreate(
                    [
                        'venue_map_template_id' => $template->id,
                        'name' => $rowData['section_name'],
                    ],
                    [
                        'code' => $this->generateUniqueSectionCode($rowData['section_name']),
                        'sort_order' => ((int) VenueMapSection::where('venue_map_template_id', $template->id)->max('sort_order')) + 1,
                    ]
                );

                if (empty($section->code)) {
                    $section->fill([
                        'code' => $this->generateUniqueSectionCode($rowData['section_name']),
                    ])->save();
                }

                $row = VenueMapRow::create([
                    'venue_map_template_id' => $template->id,
                    'venue_map_section_id' => $section->id,
                    'name' => $rowData['row_name'],
                    'seat_count' => $rowData['seat_count'],
                    'color' => $rowData['row_color'],
                    'sort_order' => ((int) VenueMapRow::where('venue_map_section_id', $section->id)->max('sort_order')) + 1,
                ]);

                for ($seatNumber = 1; $seatNumber <= $rowData['seat_count']; $seatNumber++) {
                    VenueMapSeat::create([
                        'venue_map_template_id' => $template->id,
                        'venue_map_section_id' => $section->id,
                        'venue_map_row_id' => $row->id,
                        'seat_number' => $seatNumber,
                        'seat_label' => $section->name . ' ' . $row->name . '-' . $seatNumber,
                        'sort_order' => $seatNumber,
                    ]);
                }
            }

            $this->recalculateSeatNumbers($template);
            $this->arrangeSeats($template, true);
        });

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Seats generated and auto arranged. You can still drag seats to adjust if needed.'));
    }

    public function updateSectionRow(Request $request, VenueMapTemplate $template, VenueMapRow $row)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        abort_unless((int) $row->venue_map_template_id === (int) $template->id, 404);

        $validated = $request->validate([
            'section_name' => 'required|string|max:191',
            'section_code' => 'nullable|string|max:50',
            'row_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{6}$/',
            'row_name' => 'required|string|max:50',
            'seat_count' => 'required|integer|min:1|max:1000',
            'start_seat_number' => 'nullable|integer|min:1|max:99999',
        ]);

        $sectionName = trim($validated['section_name']);
        $rowName = trim($validated['row_name']);
        $sectionCode = isset($validated['section_code']) ? trim($validated['section_code']) : null;
        $rowColor = $validated['row_color'] ?? null;
        $seatCount = (int) $validated['seat_count'];
        $startSeatNumber = isset($validated['start_seat_number']) ? max(1, (int) $validated['start_seat_number']) : 1;
        $otherSeatCount = (int) VenueMapSeat::where('venue_map_template_id', $template->id)
            ->where('venue_map_row_id', '<>', $row->id)
            ->count();

        $duplicateRow = VenueMapRow::where('venue_map_template_id', $template->id)
            ->where('id', '<>', $row->id)
            ->where('name', $rowName)
            ->whereHas('section', function ($query) use ($sectionName, $template) {
                $query->where('venue_map_template_id', $template->id)
                    ->where('name', $sectionName);
            })
            ->exists();

        if ($duplicateRow) {
            return back()
                ->withInput()
                ->with('error', __('This row already exists in the selected section.'));
        }

        if (($otherSeatCount + $seatCount) > (int) $template->expected_seat_count) {
            return back()
                ->withInput()
                ->with('error', __('Seat count cannot exceed the expected seat count.'));
        }

        DB::transaction(function () use ($template, $row, $sectionName, $sectionCode, $rowColor, $rowName, $seatCount, $startSeatNumber) {
            $oldSectionId = $row->venue_map_section_id;
            $existingSeatCount = (int) VenueMapSeat::where('venue_map_template_id', $template->id)
                ->where('venue_map_row_id', $row->id)
                ->count();

            $section = VenueMapSection::firstOrCreate(
                [
                    'venue_map_template_id' => $template->id,
                    'name' => $sectionName,
                ],
                [
                    'code' => $this->generateUniqueSectionCode($sectionName),
                    'sort_order' => ((int) VenueMapSection::where('venue_map_template_id', $template->id)->max('sort_order')) + 1,
                ]
            );

            if (empty($section->code)) {
                $section->fill([
                    'code' => $this->generateUniqueSectionCode($sectionName),
                ])->save();
            }

            $row->update([
                'venue_map_section_id' => $section->id,
                'name' => $rowName,
                'seat_count' => $seatCount,
                'start_seat_number' => $startSeatNumber,
                'color' => $rowColor,
            ]);

            VenueMapSeat::where('venue_map_template_id', $template->id)
                ->where('venue_map_row_id', $row->id)
                ->update([
                    'venue_map_section_id' => $section->id,
                ]);

            if ($seatCount < $existingSeatCount) {
                VenueMapSeat::where('venue_map_template_id', $template->id)
                    ->where('venue_map_row_id', $row->id)
                    ->orderBy('sort_order', 'desc')
                    ->orderBy('id', 'desc')
                    ->take($existingSeatCount - $seatCount)
                    ->delete();
            }

            $maxCurrentSeatNum = (int) VenueMapSeat::where('venue_map_template_id', $template->id)
                ->where('venue_map_row_id', $row->id)
                ->max('seat_number');

            for ($i = 1; $i <= ($seatCount - $existingSeatCount); $i++) {
                $tempSeatNum = $maxCurrentSeatNum + $i;
                VenueMapSeat::create([
                    'venue_map_template_id' => $template->id,
                    'venue_map_section_id' => $section->id,
                    'venue_map_row_id' => $row->id,
                    'seat_number' => $tempSeatNum,
                    'seat_label' => $section->name . ' ' . $rowName . '-' . $tempSeatNum,
                    'sort_order' => $existingSeatCount + $i,
                ]);
            }

            if ((int) $oldSectionId !== (int) $section->id) {
                VenueMapSection::where('id', $oldSectionId)
                    ->where('venue_map_template_id', $template->id)
                    ->whereDoesntHave('rows')
                    ->delete();
            }

            $this->recalculateSeatNumbers($template);
            $this->arrangeSeats($template, false);
        });

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Section row updated.'));
    }

    public function autoArrange(VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        $this->recalculateSeatNumbers($template);
        $this->arrangeSeats($template, true);

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Seats auto arranged in curved section rows.'));
    }

    public function saveCoordinates(Request $request, VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        $request->validate([
            'seats' => 'nullable|array',
            'seats.*.id' => 'required_with:seats|integer|exists:venue_map_seats,id',
            'seats.*.x_percent' => 'required_with:seats|numeric|min:0|max:100',
            'seats.*.y_percent' => 'required_with:seats|numeric|min:0|max:100',
            'seats.*.radius_percent' => 'nullable|numeric|min:0|max:100',
            'seats.*.is_accessible' => 'nullable|boolean',
            'focal_x' => 'nullable|numeric|between:0,100',
            'focal_y' => 'nullable|numeric|between:0,100',
        ]);

        DB::transaction(function () use ($request, $template) {
            if ($request->has('seats') && is_array($request->input('seats'))) {
                foreach ($request->input('seats') as $seatData) {
                    $updateData = [
                        'x_percent' => round((float) $seatData['x_percent'], 5),
                        'y_percent' => round((float) $seatData['y_percent'], 5),
                    ];

                    if (isset($seatData['radius_percent'])) {
                        $updateData['radius_percent'] = round((float) $seatData['radius_percent'], 5);
                    }

                    if (isset($seatData['is_accessible'])) {
                        $updateData['is_accessible'] = (bool) $seatData['is_accessible'];
                    }

                    VenueMapSeat::where('venue_map_template_id', $template->id)
                        ->where('id', $seatData['id'])
                        ->update($updateData);
                }
            }

            if ($request->has('focal_x') && $request->has('focal_y')) {
                VenueMapTemplateSetting::updateOrCreate(
                    ['venue_map_template_id' => $template->id],
                    [
                        'focal_x' => round((float) $request->input('focal_x'), 5),
                        'focal_y' => round((float) $request->input('focal_y'), 5),
                    ]
                );
            }
        });

        $this->syncEventVenueSeatCoordinates($template);

        $plottedSeats = $template->seats()
            ->whereNotNull('x_percent')
            ->whereNotNull('y_percent')
            ->count();

        return response()->json([
            'message' => __('Coordinates saved.'),
            'plotted_seats' => $plottedSeats,
            'expected_seats' => (int) $template->expected_seat_count,
        ]);
    }

    public function updateBackground(Request $request, VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        $request->validate([
            'background_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ]);

        $imageInfo = getimagesize($request->file('background_image')->getPathname());
        $oldBackgroundImage = $template->background_image;
        $backgroundImage = (new AppHelper)->saveUploadedFile($request->file('background_image'));

        $template->update([
            'background_image' => $backgroundImage,
            'background_width' => $imageInfo[0] ?? null,
            'background_height' => $imageInfo[1] ?? null,
        ]);

        if ($oldBackgroundImage && $oldBackgroundImage !== $backgroundImage) {
            (new AppHelper)->deleteFile($oldBackgroundImage);
        }

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Background map image updated.'));
    }

    public function publish(VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        $this->recalculateSeatNumbers($template);
        $this->arrangeSeats($template, false);

        $totalSeats = $template->seats()->count();
        $plottedSeats = $template->seats()
            ->whereNotNull('x_percent')
            ->whereNotNull('y_percent')
            ->count();

        if ($totalSeats !== (int) $template->expected_seat_count) {
            return back()->with('error', __('Generated seats must match the expected seat count before publishing.'));
        }

        if ($plottedSeats !== (int) $template->expected_seat_count) {
            return back()->with('error', __('Every generated seat must be positioned before publishing.'));
        }

        $template->update([
            'status' => VenueMapTemplate::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return redirect()
            ->route('admin.venue-maps.index')
            ->with('success', __('Venue map template published. It is now immutable.'));
    }

    public function keepDraft(VenueMapTemplate $template)
    {
        $this->authorizeAdmin();

        if ($template->status !== VenueMapTemplate::STATUS_PUBLISHED) {
            return back()->with('error', __('Only published templates can be moved back to draft.'));
        }

        $template->update([
            'status' => VenueMapTemplate::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Venue map template moved back to draft.'));
    }

    public function updateSettings(Request $request, VenueMapTemplate $template)
    {
        $this->authorizeAdmin();
        $this->ensureDraft($template);

        $validated = $request->validate([
            'template_name' => 'nullable|string|max:191',
            'version' => 'required|string|max:50',
            'expected_seat_count' => 'required|integer|min:1',
            'numbering_mode' => 'required|string|in:section,continuous',
            'counting_direction' => 'required|string|in:left_to_right,right_to_left',
            'layout_style' => 'required|string|in:curved,straight,curved_rotated',
            'show_row_name' => 'nullable|boolean',
            'seat_shape' => 'required|string|in:circle,square,rectangle,arch,horseshoe',
            'focal_x' => 'nullable|numeric|between:0,100',
            'focal_y' => 'nullable|numeric|between:0,100',
        ]);

        $oldLayoutStyle = optional($template->setting)->layout_style;
        $layoutStyleChanged = $oldLayoutStyle !== $validated['layout_style'];

        DB::transaction(function () use ($request, $template, $validated, $layoutStyleChanged) {
            $template->update([
                'name' => $validated['template_name'],
                'version' => $validated['version'],
                'expected_seat_count' => $validated['expected_seat_count'],
            ]);

            VenueMapTemplateSetting::updateOrCreate(
                ['venue_map_template_id' => $template->id],
                [
                    'numbering_mode' => $validated['numbering_mode'],
                    'counting_direction' => $validated['counting_direction'],
                    'layout_style' => $validated['layout_style'],
                    'show_row_name' => $request->has('show_row_name') ? 1 : 0,
                    'seat_shape' => $validated['seat_shape'],
                    'focal_x' => $request->input('focal_x', 50),
                    'focal_y' => $request->input('focal_y', 10),
                ]
            );

            $this->recalculateSeatNumbers($template);
            $this->arrangeSeats($template, $layoutStyleChanged ? true : false);
        });

        return redirect()
            ->route('admin.venue-maps.edit', $template)
            ->with('success', __('Template details and seat options updated successfully.'));
    }

    public function destroy(VenueMapTemplate $template)
    {
        $this->authorizeAdmin();

        if ($template->status !== VenueMapTemplate::STATUS_DRAFT) {
            return redirect()
                ->route('admin.venue-maps.index')
                ->with('error', __('Venue seat map can only be deleted if it is in draft mode.'));
        }

        if ($template->eventVenueMaps()->exists()) {
            return redirect()
                ->route('admin.venue-maps.index')
                ->with('error', __('Venue seat map cannot be deleted because it is connected to an event.'));
        }

        $template->delete();

        return redirect()
            ->route('admin.venue-maps.index')
            ->with('success', __('Venue seat map template deleted successfully.'));
    }

    public function destroySelected(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:venue_map_templates,id',
        ]);

        $deleted = 0;
        $skipped = 0;
        $deletedIds = [];

        VenueMapTemplate::whereIn('id', $request->ids)->get()->each(function ($template) use (&$deleted, &$skipped, &$deletedIds) {
            if ($template->status !== VenueMapTemplate::STATUS_DRAFT || $template->eventVenueMaps()->exists()) {
                $skipped++;
                return;
            }

            $template->delete();
            $deleted++;
            $deletedIds[] = $template->id;
        });

        return response()->json([
            'message' => __('Venue map templates deleted.'),
            'deleted' => $deleted,
            'deleted_ids' => $deletedIds,
            'skipped' => $skipped,
        ]);
    }

    private function ensureDraft(VenueMapTemplate $template)
    {
        if ($template->status !== VenueMapTemplate::STATUS_DRAFT) {
            abort(403, __('Published templates cannot be modified.'));
        }
    }

    private function recalculateSeatNumbers(VenueMapTemplate $template)
    {
        $template->loadMissing(['setting', 'sections.rows.seats']);

        // Temporarily set all seat numbers to unique large positive values (1000000000 + id) to avoid unique key conflicts during renumbering
        DB::table('venue_map_seats')
            ->where('venue_map_template_id', $template->id)
            ->update(['seat_number' => DB::raw('1000000000 + id')]);

        $numberingMode = $template->numbering_mode;
        $countingDirection = $template->counting_direction;

        // Section screen order (Left -> Center -> Right)
        $getSectionWeight = function ($section) {
            $layoutKey = $this->sectionLayoutKey($section->name);
            $map = ['left' => 1, 'center' => 2, 'right' => 3];
            return $map[$layoutKey] ?? (10 + (int) $section->sort_order);
        };

        $sections = $template->sections()->orderBy('sort_order')->orderBy('id')->get();
        $sortedSections = $sections->sort(function ($a, $b) use ($getSectionWeight) {
            $wA = $getSectionWeight($a);
            $wB = $getSectionWeight($b);
            if ($wA === $wB) {
                return $a->sort_order <=> $b->sort_order;
            }
            return $wA <=> $wB;
        })->values();

        if ($numberingMode === 'continuous') {
            // Continuous numbering:
            // Left to Right: Left -> Center -> Right
            // Right to Left: Right -> Center -> Left
            $continuousSections = $countingDirection === 'right_to_left'
                ? $sortedSections->reverse()->values()
                : $sortedSections;

            $rowsByName = [];
            foreach ($continuousSections as $section) {
                foreach ($section->rows()->orderBy('sort_order')->orderBy('id')->get() as $row) {
                    $rowNameKey = strtolower(trim($row->name));
                    if (!isset($rowsByName[$rowNameKey])) {
                        $rowsByName[$rowNameKey] = [];
                    }
                    $rowsByName[$rowNameKey][] = $row;
                }
            }

            foreach ($rowsByName as $rowGroup) {
                $firstRow = $rowGroup[0] ?? null;
                $currentSeatNumber = $firstRow ? max(1, (int) ($firstRow->start_seat_number ?: 1)) : 1;
                foreach ($rowGroup as $row) {
                    if ((int) $row->start_seat_number > 1 && $row !== $firstRow) {
                        $currentSeatNumber = (int) $row->start_seat_number;
                    }

                    $section = $row->section;
                    $seats = VenueMapSeat::where('venue_map_row_id', $row->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();

                    if ($countingDirection === 'right_to_left') {
                        $seats = $seats->reverse()->values();
                    }

                    foreach ($seats as $seat) {
                        $seat->update([
                            'seat_number' => $currentSeatNumber,
                            'seat_label' => $section->name . ' ' . $row->name . '-' . $currentSeatNumber,
                        ]);
                        $currentSeatNumber++;
                    }
                }
            }
        } else {
            // Section-wise numbering
            foreach ($sortedSections as $section) {
                foreach ($section->rows as $row) {
                    $seats = VenueMapSeat::where('venue_map_row_id', $row->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();

                    if ($countingDirection === 'right_to_left') {
                        $seats = $seats->reverse()->values();
                    }

                    $seatNumber = max(1, (int) ($row->start_seat_number ?: 1));
                    foreach ($seats as $seat) {
                        $seat->update([
                            'seat_number' => $seatNumber,
                            'seat_label' => $section->name . ' ' . $row->name . '-' . $seatNumber,
                        ]);
                        $seatNumber++;
                    }
                }
            }
        }
    }

    private function arrangeSeats(VenueMapTemplate $template, $overwrite = false, $layoutFilter = null)
    {
        $seats = $template->seats()
            ->with(['section', 'row'])
            ->get();

        if ($seats->isEmpty()) {
            return;
        }

        $layoutStyle = $template->layout_style;
        $seatsBySection = $seats->groupBy(function ($seat) {
            return $this->sectionLayoutKey(optional($seat->section)->name);
        });
        $updatedSeatIds = [];

        foreach ($seatsBySection as $layoutKey => $sectionSeats) {
            if ($layoutFilter && $layoutKey !== $layoutFilter) {
                continue;
            }

            $rowsInSection = $sectionSeats->groupBy('venue_map_row_id');
            $rowSeatCounts = $sectionSeats->groupBy('venue_map_row_id')->map->count();
            $sortedRowIds = $rowsInSection->map(function ($group) {
                return strtoupper(trim(optional($group->first()->row)->name ?: 'A'));
            })->sort()->keys()->values()->all();

            foreach ($rowsInSection as $rowId => $rowSeats) {
                $rowIndex = array_search($rowId, $sortedRowIds);

                if ($rowIndex === false) {
                    $rowIndex = 0;
                }

                $rowCount = max(1, (int) ($rowSeatCounts[$rowId] ?? 1));
                $sortedSeatsInRow = $rowSeats->sortBy('sort_order')->sortBy('id')->values();

                foreach ($sortedSeatsInRow as $seatIndex => $seat) {
                    if (!$overwrite && $seat->x_percent !== null && $seat->y_percent !== null) {
                        continue;
                    }

                    $position = $this->curvedSeatPosition(
                        $layoutKey,
                        $rowIndex,
                        $rowCount,
                        $seatIndex,
                        $layoutStyle
                    );

                    $seat->update([
                        'x_percent' => $position['left'],
                        'y_percent' => $position['top'],
                        'radius_percent' => $seat->radius_percent ?: 1.2,
                    ]);
                    $updatedSeatIds[] = $seat->id;
                }
            }
        }

        $this->syncEventVenueSeatCoordinates($template);
    }

    private function syncEventVenueSeatCoordinates(VenueMapTemplate $template)
    {
        $template->loadMissing(['sections', 'rows', 'seats.section', 'seats.row']);

        EventVenueMap::where('venue_map_template_id', $template->id)
            ->where('status', EventVenueMap::STATUS_LIVE)
            ->with(['sections', 'rows', 'seats'])
            ->chunkById(50, function ($eventMaps) use ($template) {
                foreach ($eventMaps as $eventMap) {
                    $this->reconcileEventVenueMapWithTemplate($eventMap, $template);
                }
            });
    }

    private function reconcileEventVenueMapWithTemplate(EventVenueMap $eventMap, VenueMapTemplate $template)
    {
        DB::transaction(function () use ($eventMap, $template) {
            $sectionIds = [];

            foreach ($template->sections as $section) {
                $eventSection = EventVenueSection::updateOrCreate(
                    [
                        'event_venue_map_id' => $eventMap->id,
                        'venue_map_section_id' => $section->id,
                    ],
                    [
                        'name' => $section->name,
                        'code' => $section->code,
                        'color' => $section->color,
                        'sort_order' => $section->sort_order,
                        'status' => EventVenueSeat::STATUS_AVAILABLE,
                    ]
                );

                $sectionIds[$section->id] = $eventSection->id;
            }

            $rowIds = [];

            foreach ($template->rows as $row) {
                if (!isset($sectionIds[$row->venue_map_section_id])) {
                    continue;
                }

                $eventRow = EventVenueRow::updateOrCreate(
                    [
                        'event_venue_map_id' => $eventMap->id,
                        'venue_map_row_id' => $row->id,
                    ],
                    [
                        'event_venue_section_id' => $sectionIds[$row->venue_map_section_id],
                        'name' => $row->name,
                        'seat_count' => $row->seat_count,
                        'sort_order' => $row->sort_order,
                        'status' => EventVenueSeat::STATUS_AVAILABLE,
                    ]
                );

                $rowIds[$row->id] = $eventRow->id;
            }

            $masterSeatIds = $template->seats->pluck('id')->all();

            // Pass 1: Assign temporary unique seat_number to prevent key collision when seat numbers shift
            foreach ($template->seats as $seat) {
                if (!isset($sectionIds[$seat->venue_map_section_id], $rowIds[$seat->venue_map_row_id])) {
                    continue;
                }
                $existingEventSeat = EventVenueSeat::where('event_venue_map_id', $eventMap->id)
                    ->where('venue_map_seat_id', $seat->id)
                    ->first();
                if ($existingEventSeat && (int) $existingEventSeat->seat_number !== (int) $seat->seat_number) {
                    $existingEventSeat->seat_number = 90000 + (int) $seat->id;
                    $existingEventSeat->save();
                }
            }

            // Pass 2: Save updated seat attributes and final seat_number
            foreach ($template->seats as $seat) {
                if (!isset($sectionIds[$seat->venue_map_section_id], $rowIds[$seat->venue_map_row_id])) {
                    continue;
                }

                $eventRow = EventVenueRow::find($rowIds[$seat->venue_map_row_id]);
                $eventSeat = EventVenueSeat::firstOrNew([
                    'event_venue_map_id' => $eventMap->id,
                    'venue_map_seat_id' => $seat->id,
                ]);

                $eventSeat->fill([
                    'event_venue_section_id' => $sectionIds[$seat->venue_map_section_id],
                    'event_venue_row_id' => $rowIds[$seat->venue_map_row_id],
                    'event_id' => $eventMap->event_id,
                    'section_name' => optional($seat->section)->name ?: 'Section',
                    'row_name' => optional($seat->row)->name ?: 'Row',
                    'seat_number' => $seat->seat_number,
                    'seat_label' => $seat->seat_label ?: trim((optional($seat->row)->name ?: 'Row') . ' Seat ' . $seat->seat_number),
                    'x_percent' => $seat->x_percent,
                    'y_percent' => $seat->y_percent,
                    'radius_percent' => $seat->radius_percent,
                    'is_accessible' => (bool) $seat->is_accessible,
                    'ticket_id' => $eventRow?->ticket_id,
                    'pricing_tier' => $eventRow?->pricing_tier,
                    'price' => $eventRow?->price,
                ]);

                if (!$eventSeat->exists) {
                    $eventSeat->status = EventVenueSeat::STATUS_AVAILABLE;
                }

                $eventSeat->save();
            }

            EventVenueSeat::where('event_venue_map_id', $eventMap->id)
                ->where(function ($query) use ($masterSeatIds) {
                    $query->whereNull('venue_map_seat_id')
                        ->orWhereNotIn('venue_map_seat_id', $masterSeatIds);
                })
                ->where('status', '!=', EventVenueSeat::STATUS_BOOKED)
                ->delete();

            EventVenueRow::where('event_venue_map_id', $eventMap->id)
                ->whereDoesntHave('seats')
                ->delete();

            EventVenueSection::where('event_venue_map_id', $eventMap->id)
                ->whereDoesntHave('rows')
                ->delete();
        });
    }

    private function sectionLayoutKey($sectionName)
    {
        $sectionName = strtolower((string) $sectionName);

        if (strpos($sectionName, 'left') !== false) {
            return 'left';
        }

        if (strpos($sectionName, 'right') !== false) {
            return 'right';
        }

        return 'center';
    }

    private function curvedSeatPosition($layoutKey, $rowIndex, $rowCount, $seatIndex, $layoutStyle = 'curved')
    {
        $layouts = [
            'left' => ['left' => 6, 'width' => 25, 'base_top' => 10, 'row_gap' => 4.6, 'tilt' => 1.8],
            'center' => ['left' => 36, 'width' => 28, 'base_top' => 12, 'row_gap' => 4.6, 'tilt' => 0],
            'right' => ['left' => 69, 'width' => 25, 'base_top' => 10, 'row_gap' => 4.6, 'tilt' => -1.8],
        ];

        $layout = $layouts[$layoutKey] ?? $layouts['center'];
        $progress = $rowCount === 1 ? 0.5 : $seatIndex / max(1, $rowCount - 1);
        $normalized = ($progress - 0.5) * 2;

        $left = $layout['left'] + ($progress * $layout['width']);
        $top = $layout['base_top'] + ($rowIndex * $layout['row_gap']);

        if ($layoutStyle !== 'straight') {
            if ($layoutKey === 'center') {
                $top += (1 - abs($normalized)) * 1.4;
            } else {
                $top += $normalized * $layout['tilt'];
            }
        }

        return [
            'left' => round(max(1, min(99, $left)), 5),
            'top' => round(max(2, min(96, $top)), 5),
        ];
    }

    private function uniqueVenueSlug($name)
    {
        $baseSlug = Str::slug($name) ?: 'venue';
        $slug = $baseSlug;
        $counter = 2;

        while (Venue::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function generateUniqueSectionCode($sectionName)
    {
        $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sectionName));
        $prefix = substr($cleanName, 0, 4) ?: 'SEC';

        do {
            $code = $prefix . '-' . strtoupper(Str::random(6));
        } while (VenueMapSection::where('code', $code)->exists());

        return $code;
    }
}
