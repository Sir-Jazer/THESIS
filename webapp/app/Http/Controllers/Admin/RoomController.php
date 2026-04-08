<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        return view('admin.rooms.index', [
            'rooms' => Room::orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.rooms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rooms,name'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_available' => ['required', 'boolean'],
        ]);

        Room::create($validated);

        return redirect()->route('admin.rooms.index')->with('status', 'Room created successfully.');
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rooms,name,' . $room->id],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_available' => ['required', 'boolean'],
        ]);

        $room->update($validated);

        return redirect()->route('admin.rooms.index')->with('status', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('status', 'Room archived/removed successfully.');
    }
}
