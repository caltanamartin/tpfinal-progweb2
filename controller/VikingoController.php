<?php

class VikingoController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
        $this->request  = $request;
    }

    public function ver()
    {
        Log::info("VikingoController::ver");
        $this->renderer->render("landing");
    }

    public function alta()
    {
        Log::info("VikingoController::alta (form)");
        $this->renderer->render("formAltaVikingoView");
    }

    public function procesarAlta()
    {
        $nombre = $this->request->post('nombre');
        $apodo  = $this->request->post('apodo');
        $clan   = $this->request->post('clan');
        $fuerza = $this->request->post('fuerza');

        if (!is_numeric($fuerza)) {
            Log::warning("VikingoController::procesarAlta - fuerza invalida: $fuerza");
            Redirect::toIndex();
            return;
        }

        Log::info("VikingoController::procesarAlta - nombre=$nombre");
        $this->model->alta($nombre, $apodo, $clan, (int) $fuerza);
        Redirect::toIndex();
    }

    public function editar()
    {
        $id = $this->request->get('id');

        if (!is_numeric($id)) {
            Log::warning("VikingoController::editar - id invalido: $id");
            Redirect::toIndex();
            return;
        }

        $id = (int) $id;
        Log::info("VikingoController::editar - id=$id");
        $this->renderer->render("formEditarVikingoView", $this->model->getVikingo($id));
    }

    public function procesarEditar()
    {
        $id     = $this->request->post('id');
        $fuerza = $this->request->post('fuerza');

        if (!is_numeric($id) || !is_numeric($fuerza)) {
            Log::warning("VikingoController::procesarEditar - parametros invalidos id=$id fuerza=$fuerza");
            Redirect::toIndex();
            return;
        }

        $id     = (int) $id;
        $fuerza = (int) $fuerza;
        $nombre = $this->request->post('nombre');
        Log::info("VikingoController::procesarEditar - id=$id nombre=$nombre");
        $this->model->editar($id, $nombre, $this->request->post('apodo'), $this->request->post('clan'), $fuerza);
        Redirect::toIndex();
    }

    public function eliminar()
    {
        $id = $this->request->get('id');

        if (!is_numeric($id)) {
            Log::warning("VikingoController::eliminar - id invalido: $id");
            Redirect::toIndex();
            return;
        }

        $id = (int) $id;
        Log::info("VikingoController::eliminar - id=$id");
        $this->model->eliminar($id);
        Redirect::toIndex();
    }
}
