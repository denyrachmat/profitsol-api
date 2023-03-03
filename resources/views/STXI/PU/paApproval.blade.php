<head>
  <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
  <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body style="margin: 0">
    <header class="mdc-top-app-bar">
    <div class="mdc-top-app-bar__row" style="width: 100%">
        <section class="mdc-top-app-bar__section mdc-top-app-bar__section--align-start">
            <span class="mdc-top-app-bar__title">PT Sumitronics Indonesia</span>
        </section>
        <section class="mdc-top-app-bar__section mdc-top-app-bar__section--align-end" role="toolbar">
            <span class="mdc-top-app-bar__title">PA Approval</span>
        </section>
    </div>
    </header>
    <main class="mdc-top-app-bar--fixed-adjust">
        <div class="mdc-card" style="margin: 20vh; height: 50vh">
            <div class="mdc-card__primary-action">
                <div class="mdc-card__media mdc-card__media--square">
                    <div class="mdc-card__media-content" style="text-align: center; background-color: {!! json_decode($return)->status ? '#20aafa' : '#fa7369' !!}">
                        <span class="material-icons" style="font-size: 15em; color: white;padding: 20px 0;">
                            @if(json_decode($return)->status)
                                check_circle_outline
                            @else
                                block
                            @endif
                        </span>
                        <div style="font-size: 3em; color: white">
                            <b>{!! json_decode($return)->message !!}</b>
                        </div>
                    </div>
                </div>
                <!-- ... additional primary action content ... -->
                <div class="mdc-card__ripple"></div>
            </div>
        </div>
    </main>
    {!! json_decode($return)->status !!}
</body>