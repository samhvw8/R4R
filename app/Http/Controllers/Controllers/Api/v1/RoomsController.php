<?php

namespace Wingi\Http\Controllers;

use Illuminate\Http\Request;
use Wingi\Entities\Room;
use Wingi\Entities\RoomAddress;
use Wingi\Http\Requests;

class RoomsController extends Controller
{

    /**
     * Get all Room
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $rooms = Room::all();

        return response()->json([
            'status' => true,
            'data' => [
                'rooms' => $rooms,
            ],
        ]);
    }

    /**
     * Create room
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
//        'user_id', 'price', 'area', 'amenity', 'room_add_id', 'image_album_url', 'bed'
//        'latitude', 'longitude', 'district', 'street', 'ward',

        $data = [
            'user_id' => $request->input('user_id'),
            'price' => $request->input('price'),
            'area' => $request->input('area'),
            'decripstion' => $request->input('decripstion'),
            'image_album_url' => $request->input('image_album_url'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'district' => $request->input('district'),
            'street' => $request->input('street'),
            'ward' => $request->input('ward'),
            'bed' => $request->input('bed'),
        ];


        $room_add = new RoomAddress([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'district' => $data['district'],
            'street' => $data['street'],
            'ward' => $data['ward']
        ]);

        $room_add->save();

        $room = new Room([
            'user_id' => $data['user_id'],
            'price' => $data['price'],
            'area' => $data['area'],
            'bed' => $data['bed'],
            'decripstion' => $data['decripstion'],
            'room_add_id' => $room_add->id,
            'image_album_url' => $data['image_album_url'],
        ]);

        $room->save();

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * Get room by id
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($id)
    {

        $room = Room::find($id);

        // if room  not found

        if ($room == null) {
            return response()->json([
                'status' => false,
            ]);
        }

        // user room

        return response()->json([
            'status' => true,
            'data' => [
                'room' => $room,
                'room_address' => $room->address()
            ]
        ]);
    }

    /**
     * Update Room by ID
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $room = Room::find($id);

        // if room not found

        if ($room == null) {
            return response()->json([
                'status' => false,
            ]);
        }

        // room found

        $roomAddress = RoomAddress::find($room['room_add_id']);

        $room['user_id'] = $request->input('user_id');
        $room['price'] = $request->input('price');
        $room['area'] = $request->input('area');
        $room['decripstion'] = $request->input('decripstion');
        $room['image_album_url'] = $request->input('image_album_url');
        $room['bed'] = $request->input('bed');

        $roomAddress['latitude'] = $request->input('latitude');
        $roomAddress['longitude'] = $request->input('longitude');
        $roomAddress['district'] = $request->input('district');
        $roomAddress['street'] = $request->input('street');
        $roomAddress['ward'] = $request->input('ward');


        if ($roomAddress->save() && $room->save())
            // save ok
            return response()->json([
                'status' => true,
            ]);

        // save failed
        return response()->json([
            'status' => false,
        ]);
    }

    /**
     * Delete room by ID
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $room = Room::find($id);

        // if room not found

        if ($room == null) {
            return response()->json([
                'status' => false,
            ]);
        }

        $roomAddress = RoomAddress::find($room['room_add_id']);

        if ($room->delete() && $roomAddress->delete())
            // save ok
            return response()->json([
                'status' => true,
            ]);

        // save failed
        return response()->json([
            'status' => false,
        ]);
    }

    /**
     * Search rooms nearby
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchNear(Request $request)
    {
        $data = [
            'minPrice' => $request->input('minPrice'),
            'maxPrice' => $request->input('maxPrice'),
            'minArea' => $request->input('minArea'),
            'maxArea' => $request->input('maxArea'),
            'curLat' => $request->input('latitude'),
            'curLng' => $request->input('longitude'),
            'minBed' => $request->input('minBed'),
            'maxBed' => $request->input('maxBed'),
            'radius' => $request->input('radius'),
            'limit' => $request->input('limit'),
        ];

        $rooms = Room::with('room_addresses');
        $returnRooms = [];
        $returnRoomsNumber = 0;
        foreach ($rooms as $room) {
            $lat = $room['latitude'];
            $lng = $room['longitude'];
            $distance = 6371 * acos(sin($data['curLat']) * sin($lat) + cos($data['curLat']) * cos($lat) * cos($data['curLng'] - $lng));
            if ($distance <= $data['radius']) {
                if (($room['bed'] >= $data['minBed']) && ($room['bed'] <= $data['maxBed'])) {
                    if (($room['area'] >= $data['minArea']) && ($room['area'] <= $data['maxArea'])) {
                        if (($room['price'] >= $data['minPrice']) && ($room['price'] <= $data['maxPrice'])) {
                            $returnRooms . array_push($room);
                            $returnRoomsNumber += 1;
                        }
                    }
                }
            }
            if ($returnRoomsNumber > $data['limit'])
                break;
        }
        //        if ($unit == 'km') $radius = 6371.009; // in kilometers
//        elseif ($unit == 'mi') $radius = 3958.761; // in miles
        //return $radius * acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lng1 - $lng2));
        return response()->json($returnRooms);
    }
}