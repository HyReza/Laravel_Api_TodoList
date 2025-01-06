<?php

namespace App\Http\Controllers\Api;

use App\Models\Todo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TodoResource;
use Exception;
use App\Models\Log as LogModel;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Schema(
 *     schema="Todo",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Buy groceries"),
 *     @OA\Property(property="description", type="string", example="Milk, bread, and eggs"),
 *     @OA\Property(property="completed", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-02T00:00:00Z")
 * )
 */
class TodoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/todos",
     *     summary="Get all todos",
     *     description="Retrieve a list of all todos",
     *     @OA\Response(
     *         response=200,
     *         description="List of todos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Todo")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $todolist = Todo::latest()->get();
            Log::channel('stack')->info("Accessed Todo List");
            Log::channel('slack')->info("Accessed Todo List");
            LogModel::record(auth()->user(), 'Accessed Todo List', 'GET');
        } catch (Exception $error) {
            Log::channel('stack')->error("Failed : ", ['message' => $error->getMessage()]);
            Log::channel('slack')->error("Failed : ", ['message' => $error->getMessage()]);
        }

        return TodoResource::collection($todolist);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/todos",
     *     summary="Create a new todo",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Todo")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Todo created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Todo")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|min:3|max:255',
            'description' => 'required|min:3|max:255',
            'completed' => 'required|in:0,1',
        ]);

        try {
            $todo = Todo::create($request->all());
            Log::channel('stack')->info("Todo List Created");
            Log::channel('slack')->info("Todo List Created");
            LogModel::record(auth()->user(), 'Todo List Created', 'POST');

            return response()->json([
                'message' => 'Todo Created Sucessfully',
                'data' => new TodoResource($todo)
            ], 201);
        } catch (Exception $error) {
            Log::channel('stack')->error("Failed : ", ['message' => $error->getMessage()]);
            Log::channel('slack')->error("Failed : ", ['message' => $error->getMessage()]);

            return response()->json([
                'message' => 'Failed To Create Todo',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/todos/{id}",
     *     summary="Get a specific todo",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the todo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Todo details",
     *         @OA\JsonContent(ref="#/components/schemas/Todo")
     *     )
     * )
     */
    public function show(string $id)
    {
        $todo = Todo::find($id);

        if ($todo == null) {
            Log::channel('stack')->warning("Todo List Not Found");
            Log::channel('slack')->warning("Todo List Not Found");
            LogModel::record(auth()->user(), 'Todo List Not Found', 'GET');

            return response()->json([
                'message' => 'Todo Not Found',
            ], 404);
        } else {
            Log::channel('stack')->info("Todo Retrieved Successfully");
            Log::channel('slack')->info("Todo Retrieved Successfully");

            LogModel::record(auth()->user(), 'Todo Retrieved Successfully', 'GET');
            return response()->json([
                'message' => 'Todo Retrieved Successfully',
                'data' => new TodoResource($todo)
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/todos/{id}",
     *     summary="Update a specific todo",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the todo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Todo")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Todo updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Todo")
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $todo = Todo::find($id);

        if ($todo == null) {
            Log::channel('stack')->warning("Todo List For Edit Not Found");
            Log::channel('slack')->warning("Todo List For Edit Not Found");
            LogModel::record(auth()->user(), 'Todo List For Edit Not Found', 'GET');

            return response()->json([
                'message' => 'Todo Not Found',
            ], 404);
        } else {
            $request->validate([
                'title' => 'min:3|max:255',
                'description' => 'min:3|max:255',
                'completed' => 'in:0,1',
            ]);

            try {
                $todo->update($request->all());

                Log::channel('stack')->info("Todo List Updated");
                Log::channel('slack')->info("Todo List Updated");
                LogModel::record(auth()->user(), 'Todo List Updated', 'PUT');

                return response()->json([
                    'message' => 'Todo Updated Sucessfully',
                    'data' => new TodoResource($todo)
                ]);
            } catch (Exception $error) {
                Log::channel('stack')->error("Failed : ", ['message' => $error->getMessage()]);
                Log::channel('slack')->error("Failed : ", ['message' => $error->getMessage()]);
                LogModel::record(auth()->user(), 'Todo List Failed Updated', 'PUT');

                return response()->json([
                    'message' => 'Todo List Failed Updated',
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/todos/{id}",
     *     summary="Delete a specific todo",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the todo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Todo deleted successfully"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $todo = Todo::find($id);

        if ($todo == null) {
            Log::channel('stack')->warning("Todo List For Delete Not Found");
            Log::channel('slack')->warning("Todo List For Delete Not Found");
            LogModel::record(auth()->user(), 'Todo List For Delete Not Found', 'GET');

            return response()->json([
                'message' => 'Todo Not Found',
            ], 404);
        } else {
            try {
                $todo->delete();

                Log::channel('stack')->info("Todo List Deleted");
                Log::channel('slack')->info("Todo List Deleted");
                LogModel::record(auth()->user(), 'Todo List Deleted', 'DELETE');

                return response()->json([
                    'message' => 'Todo Deleted Sucessfully',
                ]);
            } catch (Exception $error) {
                Log::channel('stack')->error("Failed : ", ['message' => $error->getMessage()]);
                Log::channel('slack')->error("Failed : ", ['message' => $error->getMessage()]);
                LogModel::record(auth()->user(), 'Todo List Failed Deleted', 'DELETE');

                return response()->json([
                    'message' => 'Todo List Failed Deleted',
                ]);
            }
        }
    }
}
