<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Tag;
use App\Exercise;
use App\File;

use Auth;
use Storage;


class ExerciseController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

   /**
     * Desplegar la lista de ejercicios a
     * los que tiene acceso el usuario actual
     *
     */
    public function index()
    {

        $exercises = Auth::user()->exercises;

        if(count($exercises) == 0){
            return redirect('/home')->with('success', 'No hay ejercicios en la lista');;
        }

        return view('exercises.index', compact('exercises'));

    }

    /**
     * Mostrar el formulario para la creación de un ejercicio
     *
     */
    public function create(Request $request)
    {
        $tags       = Tag::all(); 
        $requestTag = $request->input('tag');

        return view('exercises.create', compact('tags', 'requestTag'));

    }

    /**
     * Manejar el almacenamiento de información
     * en las distintas tablas relacionadas
     * a un ejercicio
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Save exercise

        $request->validate([
            'title'=>'required',
            'description'=> 'required',
            'tag' => 'required'
          ]);

        $exercises = new Exercise([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'tag_id'=> $request->get('tag')
        ]);

        $exercises->save();


        // Save exercise_user

        $user = Auth::user()->id;
        $exercises->users()->attach($user);

        if($request->get('users') != "")
        {   
            $users = explode(',', $request->get('users'));
            //Sanatizar array
            for($i=0; $i<count($users); $i++)
            {
                $users[$i] = trim($users[$i]);
            }
            $users = array_unique($users);
            //Guardar
            foreach ($users as $user_id)
            {
                $exercises->users()->attach(trim($user_id));
            }
        }


        // Save files

        if($request->hasFile('attachment'))
        {
            $allowedfileExtension = ['pdf','jpg','png','docx'];
            $attachments = $request->file('attachment');

            foreach ($attachments as $attachment)
            {

                $attachmentName = $attachment->getClientOriginalName();
                $extension      = $attachment->getClientOriginalExtension();
                $check          = in_array($extension,$allowedfileExtension);
                $filename       = $attachmentName.'_'.time().'.'.$extension;


                if($check)
                {
                    // Storage
                    $attachment->storeAs('public/'.$exercises->id, $filename);

                    //Save in database
                    $files = new File([
                        'exercise_id' => $exercises->id,
                        'url' =>  $filename
                    ]);
                    $files->save();

                }

            }
        }

        return redirect('/exercises/'.$exercises->id)->with('success', 'Ejercicio agregado correctamente');

    }

    /**
     * Mostrar un ejercicio específico
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $exercise = Exercise::find($id);
        $files    = File::where('exercise_id', $id)->get();

        return view('exercises.show', compact('exercise', 'files'));

    }

    /**
     * Mostrar el formulario de edición de un ejercicio
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $exercise = Exercise::find($id);
        $users = $exercise->users;
        dd($users);

        foreach($users as $user){

            if($user->id == Auth::user()->id){

                $tags       = Tag::all();
                $files      = File::where('exercise_id', $id)->get();
                
                return view('exercises.edit', compact('exercise', 'tags', 'users', 'files'));

            }
        
        }

        return $this->show($id);

    }

    /**
     * Actualizar los datos de un ejercicio editado
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'=>'required',
            'description'=> 'required',
            'tag' => 'required'
          ]);


        $exercise = Exercise::find($id);
        $users = $exercise->users;

        foreach($users as $user){

                if($user->id == Auth::user()->id){

                    // Update exercise

                    $exercise->title = $request->get('title');
                    $exercise->description = $request->get('description');
                    $exercise->tag_id = $request->get('tag');
                    $exercise->save();

                    // Update exercise_user

                    if($request->get('users') != "")
                    {
                        foreach (explode(',', $request->get('users')) as $id)
                        {
                            $exercise->users()->attach($id);
                        }
                    }
                
                    // Update files
                
                    if($request->hasFile('attachment'))
                    {
                        $allowedfileExtension = ['pdf','jpg','png','docx'];
                        $attachments = $request->file('attachment');
                    
                        foreach ($attachments as $attachment)
                        {
                        
                            $attachmentName = $attachment->getClientOriginalName();
                            $extension      = $attachment->getClientOriginalExtension();
                            $check          = in_array($extension,$allowedfileExtension);
                            $filename       = $attachmentName.'_'.time().'.'.$extension;
                        
                        
                            if($check)
                            {
                                // Storage
                                $attachmDeleteDeleteent->storeAs('public/'.$exercises->id, $filename);
                            
                                //Save in database
                                $files = new File([
                                    'exercise_id' => $exercises->id,
                                    'url' =>  $filename
                                ]);
                                $files->save();
                                
                            }
                        
                        }
                    }
                
                    // Return
                
                    return redirect('/home')->with('success', 'Ejercicio agregado correctamente');

            }
        }

        return $this->show($id);
        
    }

    /**
     * Eliminar un ejercicio determinado
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        $exercise = Exercise::find($id);
        $users = $exercise->users;

        foreach($users as $user){

            if($user->id == Auth::user()->id){

                foreach($users as $user){
                    $user->exercises()->detach($exercise);
                }
        
                // Delete files
        
                $files = File::where('exercise_id', $id)->delete();
        
                //Delete from exercise table
        
                $exercise->delete();
        
                // Return
        
                return redirect('/exercises')->with('success', 'El ejercicio fue eliminado exitosamente');
                    
            }

        }

        return $this->show($id);


    
    }

}
