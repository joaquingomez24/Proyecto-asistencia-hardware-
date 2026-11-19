@if ($errors->any())
    <div class="alerta error" role="alert" style="align-items: flex-start;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-top: 3px;"></i>
        <ul style="margin: 0; padding-left: 18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
