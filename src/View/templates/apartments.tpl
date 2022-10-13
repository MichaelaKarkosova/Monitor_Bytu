{$i = 0}
{foreach $apartments as $item}
    {if $item.url|strstr:"bezrealitky"}
         {$source = "bezrealitky"}
    {/if}
    {if $item.url|strstr:"reality.idnes"}
        {$source = "idnes"}
    {/if}
    {if $item.url|strstr:"realitymix"}
        {$source = "realityMix"}
    {/if}
    {if $item.zvirata eq "1"}
        {$zvirata = "Ano"}
    {elseif $item.zvirata eq "0"}
        {$zvirata = "Ne"}
    {else}
        {$zvirata = "Neuvedeno"}
    {/if}
    {if $item.vytah eq "1"}
        {$vytah = "Ano"}
    {elseif $item.vytah eq "0"}
        {$vytah = "Ne"}
    {else}
        {$vytah = "Neuvedeno"}
    {/if}
    {if $item.balkon eq "1"}
        {$balkon = "Ano"}
    {elseif $item.balkon eq "0"}
        {$balkon = "Ne"}
    {else}
        {$balkon = "Neuvedeno"}
    {/if}
    {if $item.patro === null}
        {$item.patro = "Neuvedeno"}
    {/if}

    <div class="container-sm themed-container text-center">
        <h5><b>{$item.name}</b></h5>
        <h6>{$item.part}</h6>
        <h6>{$item.pricetotal} Kč/měsíc </h6>
        <h6>{$item.dispozice}</h6>
        <h6>{$item.vymera} m2</h6>
        {if $source=="idnes"}
            <h6> Cena je BEZ poplatků, jen nájem</h6>
        {/if}
        <h6 id="distance{$i}">Vzdálenost od centra:  </h6>
        <h6 id="metro{$i}">Nejbližší metro:  </h6>
        <h6><b>Zdroj: </b>{$source}</h6>
        <p>

            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{$i}" aria-expanded="false" aria-controls="collapseExample">
                Info
            </button>
        </p>
        <div class="collapse" id="collapse{$i}">
            <div class="card card-body">
                <h6 class="addres">{$item.longpart}</h6>
                    <h6><b>Výměra: </b> {$item.vymera} m2</h6>
             <h6><b>Dispozice: </b> {$item.dispozice}</h6>
              <h6><b>Patro: </b> {$item.patro}.</h6>
                <h6><b>Výtah: </b> {$vytah}</h6>
                  <h6> <b>Stav:</b> {$item.stav}</h6>
                <h6><b>Domácí zvířata: </b>{$zvirata}</h6>
                <h6><b>Balkon: </b>{$balkon}</h6>
                <h5><b>Cena: </b>{$item.price} + {$item.pricetotal - $item.price} Kč  </h5>
                {if $source=="idnes" or $source=="realityMix"}
                    <h5> Cena je BEZ poplatků, jen nájem</h5>
                {/if}
                <a class="url" href="{$item.url}" target="blank">Podrobnosti</a>

            </div>
        </div>
     {$i = $i+1}
    </div>
{/foreach}
