<?php

namespace App\Http\Controllers;

use App\Lesson;
use App\Record;

use App\Services\WebsocketClient;
use Illuminate\Http\Request;
use Validator;

class RecordController extends Controller
{
    const AVAILABLE_PERIOD = [
        '08:00 - 09:00',
        '18:00 - 19:00',
        '19:00 - 20:00',
        '20:00 - 21:00',
    ];
    const MAX_PLACES = 7;

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ]);
        }

        $lessonData = ['date' => $request->get('date')];
        $lessons = Lesson::with('records')->where($lessonData)->get();
        return response()->json([
            'success' => true,
            'lessons' => $lessons
        ]);
    }

    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'date' => 'required',
            'period' => 'required',
            'places' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ]);
        }

        if (!in_array($request->get('period'), self::AVAILABLE_PERIOD)) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'period' => ['not available period']
                ]
            ]);
        }
        $lessonData = ['date' => $request->get('date'), 'period' => $request->get('period')];
        $lesson = Lesson::with('records')->where($lessonData)->first();
        if (!$lesson) {
            $lesson = new Lesson($lessonData);
            $lesson->save();
        }


        $records = $lesson->records->toArray();
        $places = $this->countPlaces($records);
        if (($places + (int)$request->get('places')) > self::MAX_PLACES) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'places' => ['sorry there are no free places']
                ]
            ]);
        }
        $record = new Record();
        $record->fill($request->only(['name', 'phone', 'places']));
//        $lesson->records()->save($record);

        $this->writeToSocket('new_record', '');

        return response()->json([
            'success' => true,
            'data' => $record,
            'records' => $records
        ]);
    }

    /**
     * @param array $records
     * @return int
     */
    private function countPlaces(array $records): int
    {
        $places = 0;
        foreach ($records as $record) {
            $places += (int)$record['places'];
        }

        return $places;
    }

    private function writeToSocket($action, $message)
    {
        $address = "127.0.0.1";
        $port = "8888";
        $data = [
            'action' => $action,
            'message' => $message
        ];
        $WebSocketClient = new WebsocketClient($address, $port);
//        $response = $WebSocketClient->sendData(json_encode($data));
        $response = $WebSocketClient->sendData('hello');

        unset($WebSocketClient);

        return $response;
    }
}
