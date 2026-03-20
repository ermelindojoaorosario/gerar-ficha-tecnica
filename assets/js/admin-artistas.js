jQuery(function($){

    console.log('Mozbeats artistas JS carregado');

    // busca
    $('#mozbeats-busca-artista').on('keyup', function(){
        let valor = $(this).val().toLowerCase().trim();

        $('.mozbeats-artista-card').each(function(){
            let nome = ($(this).data('name') || '').toString();

            $(this).toggle(nome.includes(valor));
        });
    });

    // ajax genero
    $('.mozbeats-set-genero').on('click', function(e){
        e.preventDefault();

        var $btn = $(this);
        var term_id = $btn.data('id');
        var genero  = $btn.data('genero');

        $.post(mozbeats_ajax.url, {
            action: 'mozbeats_set_genero',
            term_id: term_id,
            genero: genero,
            nonce: mozbeats_ajax.nonce
        }, function(response){

            if(response.success){
                $btn.closest('.mozbeats-artista-card')
                    .find('p strong')
                    .first()
                    .text('Gênero: ' + genero);

                $btn.text('✔ Salvo');
            }

        });

    });

});
