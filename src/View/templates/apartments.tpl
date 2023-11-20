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
    {if $item.url|strstr:"sreality"}
        {$source = "sreality"}
        {$item.url = $item.url|replace:"https://www.sreality.cz/api/cs/v2/estates/" : "http://sreality.cz/detail/pronajem/byt/kk/id/"}
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
        {$realPerM3 =($item.pricetotal - $item.price)/$item.vymera}
    {if $item.patro === null}
        {$item.patro = "Neuvedeno"}
    {/if}
        {if $item.stav === null || $item.stav eq ""}
        {$item.stav = "Neuvedeno"}
    {/if}
    {if $item.pricetotal - $item.price === 0 || (($realPerM3-$item.average)/$realPerM3*100) == "0"}
        {$item.value = "Průměrná cena"}
        {$cssclass = "average"}
    {elseif $item.average eq "" || $item.stav === null}
        {$item.value = "Neznámá cena"}
        {$cssclass = "unknown"}
    {elseif $item.average < ($item.pricetotal - $item.price)/$item.vymera}
        {$item.value = "Nadprůměrná cena"}
        {$cssclass = "high"}
    {elseif $item.average > ($item.pricetotal - $item.price)/$item.vymera}
        {$item.value = "Podprůměrná cena"}
        {$cssclass = "low"}
    {/if}

    <div class="container-sm themed-container text-center">
        {if $item.first}<h5>Inzerát vložen: {$item.first|date_format:"%d. %m. %y, %H:%M"}</h6>{/if}
        <h6>Poslední aktualizace: {$item.imported|date_format:"%d. %m. %y, %H:%M"}</h6>
        <div class="{$cssclass}">
        <h6 class="inside">{$item.value}  {if $item.value neq "Neznámá cena" && $item.value neq "Průměrná cena"} ({(($realPerM3-$item.average)/$realPerM3*100)|round:2}%){/if}</div></h6>
        {if $item.value eq "Neznámá cena"}<h5>Pro výpočet ceny musí být vyplněna část i stav. {/if}</h5>
        <h5><b>{$item.name}</b></h5>
        <h6>{$item.part}</h6>
        <h6>{$item.pricetotal} Kč/měsíc {if $source=="sreality" or $source=="realityMix"}
                    <b>(+ poplatky)</b>
                {/if}</h6>
        <h6>{$item.dispozice}</h6>
        <h6>{$item.vymera} m2</h6>
        <h6>{$item.vybaveni}</h6>
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
                <h5><b>Cena: </b> {$item.pricetotal - $item.price} + {$item.price} Kč   </h5>
                <a class="url btn btn-primary" href="{$item.url}" target="blank">Podrobnosti</a>

            </div>
        </div>
     {$i = $i+1}
    </div>

{/foreach}
