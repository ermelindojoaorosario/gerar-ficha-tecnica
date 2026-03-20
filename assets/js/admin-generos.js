jQuery(document).ready(function($){

    const wrapper = $('#mozbeats-generos-wrapper');
    const input = $('#novo-genero');
    const textarea = $('#custom_generos');

    function atualizarTextarea(){
        let valores = [];
        wrapper.find('.mozbeats-tag').each(function(){
            valores.push($(this).data('value'));
        });
        textarea.val(valores.join("\n"));
        atualizarContador();
    }

    function atualizarContador(){
        let total = wrapper.find('.mozbeats-tag').length;
        $('#mozbeats-contador').text(total + " gêneros adicionados");
    }

    // Adicionar gênero
    $('#add-genero').on('click', function(){

        let novo = input.val().trim();
        if(novo === '') return;

        // Evitar duplicado
        let existe = false;
        wrapper.find('.mozbeats-tag').each(function(){
            if($(this).data('value').toLowerCase() === novo.toLowerCase()){
                existe = true;
            }
        });

        if(existe){
            alert("Esse gênero já existe.");
            return;
        }

        let tag = $(`
            <span class="mozbeats-tag" data-value="${novo}">
                ${novo}
                <button type="button" class="remove-genero">×</button>
            </span>
        `);

        tag.hide();
        wrapper.append(tag);
        tag.fadeIn(200);

        input.val('');
        atualizarTextarea();
    });

    // Enter adiciona também
    input.on('keypress', function(e){
        if(e.which === 13){
            e.preventDefault();
            $('#add-genero').click();
        }
    });

    // Remover gênero com animação
    wrapper.on('click', '.remove-genero', function(){
        $(this).parent().fadeOut(200, function(){
            $(this).remove();
            atualizarTextarea();
        });
    });

    atualizarContador();
});