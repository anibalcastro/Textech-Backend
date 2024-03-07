<?php

namespace App\Http\Controllers\Api\V1;

use DateTime;
use App\Models\Semana;
use Illuminate\Http\Request;
use App\Models\TrabajoSemanal;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class SemanaController extends Controller
{

    public function generarSemanasMensuales(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $meses = $request->meses;

        $fechasSemanas = [];

        // Convertir la fecha inicial a objeto DateTime
        $fechaActual = new DateTime($fechaInicio);

        // Recorrer los meses
        for ($i = 0; $i < $meses; $i++) {
            // Clonar el objeto DateTime para evitar modificar el original
            $fechaInicioMes = clone $fechaActual;

            // Establecer la fecha de inicio del mes (primer día del mes)
            $fechaInicioMes->modify('first day of this month');

            // Establecer la fecha de final del mes (último día del mes)
            $fechaFinalMes = clone $fechaInicioMes;
            $fechaFinalMes->modify('last day of this month');

            // Avanzar al primer lunes del mes
            $fechaInicioSemana = clone $fechaInicioMes;
            $fechaInicioSemana->modify('Monday');

            // Recorrer las semanas del mes
            while ($fechaInicioSemana <= $fechaFinalMes) {
                // Establecer la fecha final de la semana (domingo de esa semana)
                $fechaFinalSemana = clone $fechaInicioSemana;
                $fechaFinalSemana->modify('Sunday');

                // Obtener el número de semana y el año para la fecha actual
                $nSemana = $fechaInicioSemana->format('W');
                $anio = $fechaInicioSemana->format('Y');

                // Agregar las fechas de inicio y final de la semana al array de fechas
                $fechasSemanas[] = [
                    'semanaN' => 'Semana #' . $nSemana . ' ' . $anio,
                    'fechaInicio' => $fechaInicioSemana->format('Y-m-d'),
                    'fechaFinal' => $fechaFinalSemana->format('Y-m-d')
                ];

                // Avanzar a la siguiente semana
                $fechaInicioSemana->modify('+1 week');
            }

            // Avanzar al siguiente mes
            $fechaActual->modify('+1 month');
        }

        $resultado = $this->guardarSemanasGeneradas($fechasSemanas);

        if ($resultado) {
            return response()->json([
                'message' => 'Semanas generadas y almacenadas correctamente.',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Son semanas duplicadas, no se registraron.',
                'status' => 200
            ], 200);
        }
    }

    public function guardarSemanasGeneradas($arraySemanas)
    {
        // Convertir el array de semanas en una colección
        $weeksToStore = collect($arraySemanas);

        // Recuperar las semanas existentes de la base de datos
        $existingWeeks = Semana::whereIn('fechaInicio', $weeksToStore->pluck('fechaInicio'))
            ->whereIn('fechaFinal', $weeksToStore->pluck('fechaFinal'))
            ->get();

        // Filtrar las semanas recibidas para guardar solo las que no están duplicadas
        $uniqueWeeks = $weeksToStore->reject(function ($newWeek) use ($existingWeeks) {
            return $existingWeeks->contains(function ($existingWeek) use ($newWeek) {
                return $existingWeek->fechaInicio === $newWeek['fechaInicio'] && $existingWeek->fechaFinal === $newWeek['fechaFinal'];
            });
        });

        // Guardar las semanas no duplicadas en la base de datos
        if (count($uniqueWeeks) >= 1) {
            foreach ($uniqueWeeks as $newWeek) {
                Semana::create([
                    'fechaInicio' => $newWeek['fechaInicio'],
                    'fechaFinal' => $newWeek['fechaFinal'],
                    'nSemana' => $newWeek['semanaN'],
                ]);
            }

            return true;
        }

        return false;
    }


    public function asignarOrdenes(Request $request)
    {
        // Obtener los datos de los campos semana y ordenes
        $ordenes = $request->input('ordenes', []);
        $idSemana = $request->input('semana');

        if (!empty($ordenes)) {
            foreach ($ordenes as $item) {
                $objTrabajoSemanal = new TrabajoSemanal();
                $objTrabajoSemanal->idOrden = $item;
                $objTrabajoSemanal->idSemana = $idSemana;
                $objTrabajoSemanal->estado = false;

                try {
                    $objTrabajoSemanal->save();
                } catch (\Exception $e) {
                    // Manejar la excepción, si es necesario
                    return response()->json(['mensaje' => 'Error al guardar los datos', 'error' => $e->getMessage(),'status' => 500], 500);
                }
            }

            return response()->json(['mensaje' => 'Ordenes asignadas correctamente', 'status' => 200], 200);
        }

        return response()->json(['mensaje' => 'No hay ordenes por agregar', 'status' => 422], 422);
    }

    public function eliminarOrdenes($idTrabajoSemanal)
    {
        $trabajoSemanal = TrabajoSemanal::where('id', $idTrabajoSemanal)->first();

        if ($trabajoSemanal) {
            // Si el modelo existe, eliminarlo
            $trabajoSemanal->delete();
            return response()->json(['mensaje' => 'El trabajo semanal ha sido eliminado correctamente', 'status' => 200], 200);
        } else {
            // Si el modelo no existe, mostrar un mensaje de error
            return response()->json(['mensaje' => 'El trabajo semanal no existe', 'status' => 404], 404);
        }
    }


    public function retornarSemanasTrabajo () {
        $semanasTrabajo = TrabajoSemanal::with(['semana', 'orden'])->get();

        // Verificar si se encontraron semanas de trabajo
        if ($semanasTrabajo->isEmpty()) {
            // Si no se encontraron semanas de trabajo, devolver un mensaje de error
            return response()->json(['mensaje' => 'No se encontraron semanas de trabajo', 'status' => 404], 404);
        } else {
            // Si se encontraron semanas de trabajo, devolverlas como respuesta
            return response()->json(['data' => $semanasTrabajo, 'status' => 200], 200);
        }
    }


    public function retornarSemanas () {
        $semanas = Semana::all();

        // Verificar si se encontraron semanas de trabajo
        if ($semanas->isEmpty()) {
            // Si no se encontraron semanas de trabajo, devolver un mensaje de error
            return response()->json(['mensaje' => 'No se encontraron semanas', 'status' => 404], 404);
        } else {
            // Si se encontraron semanas de trabajo, devolverlas como respuesta
            return response()->json(['data' => $semanas, 'status' => 200], 200);
        }
    }


    public function cambiarEstado ($id) {
        $trabajoSemanal = TrabajoSemanal::find($id);

        $trabajoSemanal->estado = true;

        $trabajoSemanal->update();

        return response()->json([
            'mensaje' => 'El trabajo semanal se ha modificado de manera correcta',
            'status' => 200,
        ], 200);



    }
}
