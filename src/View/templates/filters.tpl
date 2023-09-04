{$minprice = $filters['pricemin']}
{$maxprice = $filters['pricemax']}
{$areamax = $filters['areamax']}
{$areamin = $filters['areamin']}
{$stav = "Použité filtry:"}
{if $filters['pricemax'] eq null}
    {$maxprice = 55000}
{/if}
{if $filters['pricemin'] eq null}
    {$minprice = 5000}
{/if}

{if $filters['areamin'] eq null}
    {$areamin = 4}
{/if}
{if $filters['areamax'] eq null}
    {$areamax = 300}
{/if}


                <label for="order">Řadit podle</label>
                        <form type="GET">
                <select name="order" id="order">
                    <option value="new" {if $order eq "new"}selected {/if}>Od nejnovějšího</option>
                    <option value="old" {if $order eq "old"}selected {/if}>Od nejstaršího</option>
                    <option value="cheap" {if $order eq "cheap"}selected="selected"{/if}>Od nejlevnějšího</option>
                    <option value="expensive"{if $order eq "expensive"}selected="selected"{/if}>Od nejdražšího</option>
                    <option value="part" {if $order eq "part"}selected{/if}>Mětské části</option>
                    <option value="areamin" {if $order eq "areamin"}selected {/if}>Od nejmenšího</option>
                    <option value="areamax" {if $order eq "areamax"}selected {/if}>Od největšího</option>
                </select>
                <div class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-450 align-items-center justify-content-between">
                        <strong class="mb-1">Zdroj</strong>
                    </div>
                    <div class="d-flex  p-2 flex-wrap mb-3">
                    <select name="source" id="source">
                      <option value = "" {if !isset($filters['source'])}selected="selected"{/if} >Nezáleží</option>
                        {foreach $source as $f}
                            {if $f.zdroj eq NULL or $f.zdroj eq "0"}
                                {$f.zdroj = "Neuvedeno"}
                            {/if}

                            <div class="full">
    
                                <option value = "{$f.zdroj}" {if $filters['source'] eq "{$f.zdroj}"}selected="selected"{/if} >{$f.zdroj} ({$f.count})</option>
                                <label for="zdroj_{$f.zdroj}">{$f.zdroj} ({$f.count})</label>
                            </div>
                        {/foreach}
                        </select>
                    </div>
                </div>



                <div class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-450 align-items-center justify-content-between">
                        <strong class="mb-1">Část Prahy</strong>
                    </div>
                    <div class="d-flex  p-2 flex-wrap mb-3">
                        {foreach $parts as $f}
                            {if $f.part eq NULL}
                              {$f.part = "Neuvedeno"}
                                {/if}
                            {if $f.part neq NULL}
                            <div class="half">
                                <input type="checkbox" id="part_{$f.part}" value="{rawurlencode($f.part)}" {if isset($filters['part']) && in_array(rawurlencode($f['part']), $filters['part'])}checked{/if} name="part[]">
                                <label for="part_{$f.part}">{$f.part} ({$f.count})</label>
                            </div>
                        

                            {/if}
                        {/foreach}
                    </div>
                </div>
                <div class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Plocha</strong>
                    </div>
                    <div class="col-10 mb-1"> Od <input type="number" min="4" id="areamin" value="{$areamin}" step="1" name="areamin" size=""> do <input type="number" value="{$areamax}" min="0" size="5" id="areamax" step="1" name="areamax"> m2</div>
                </div>
                            
      
                <div class="list-group-item list-group-item-action py-3 lh-sm" aria-current="true">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Cena</strong>
                    </div>
                    <div class="col-10 mb-1"> Od <input type="number" value="{$minprice}" min="4000" id="pricemin" step="500" name="pricemin" size="5">
                        do <input type="number" min="4000" size="5" id="pricemax" value="{$maxprice}" step="500" name="pricemax"> Kč</div>

                </div>
                            
                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Stav</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $conditions as $f}
                            {if $f.stav eq NULL}
                                {$f.stav = "Neuvedeno"}
                            {/if}
                            <div class="full">
                            <input type="checkbox" id="stav_{$f.stav}" value="{rawurlencode($f.stav)}" {if isset($filters['stav']) && in_array(rawurlencode($f['stav']), ($filters['stav']))}checked{/if} name="stav[]"">
                                {if isset($filters['stav']) && in_array(rawurlencode($f['stav']), ($filters['stav']))}
                        
                                {/if}

                                <label for="stav_{rawurlencode($f.stav)}">{$f.stav} ({$f.count})</label>
                            
                            </div>
                        {/foreach}
                    </div>
                </div>
                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Dispozice</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $sizes as $f}
                            {if $f.dispozice eq NULL or $f.dispozice eq "0"}
                                {$f.dispozice = "Neuvedeno"}
                            {/if}

                            <div class="full">
                                <input type="checkbox" id="dispozice_{rawurlencode($f.dispozice)}" value="{rawurlencode($f.dispozice)}" {if isset($filters['size']) && in_array(rawurlencode($f['dispozice']), $filters['size'])}checked{/if} name="size[]"">
                                <label for="dispozice_{$f.dispozice}">{$f.dispozice} ({$f.count})</label>
                            </div>
                        {/foreach}
                    </div>
                </div>
                                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Vybavení</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $furniture as $f}
                            {if $f.vybaveni eq NULL or $f.vybaveni eq "0"}
                                {$f.vybaveni = "Neuvedeno"}
                            {/if}

                            <div class="full">
                                <input type="checkbox" id="vybaveni_{rawurlencode($f.vybaveni)}" value="{rawurlencode($f.vybaveni)}" {if isset($filters['furniture']) && in_array(rawurlencode($f['vybaveni']), $filters['furniture'])}checked{/if} name="furniture[]"">
                                <label for="vybaveni_{$f.vybaveni}">{$f.vybaveni} ({$f.count})</label>
                            </div>
                        {/foreach}
                    </div>
                </div>

                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm" aria-current="true">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Patro</strong>
                    </div>

                    <div class="col-10 mb-1">
                        {foreach $stairs as $f}
                             {if $f.patro eq "0"}
                                {$f.patro = "0"}
                            {elseif $f.patro eq null}
                                {$f.patro = "Neuvedeno"}
                            {/if}
                                                       
                            <div class="full">
                                <input type="checkbox" id="patro_{$f.patro}" value="{$f.patro}" {if isset($filters['stairs']) && in_array($f['patro'], $filters['stairs'])}checked{/if} name="stairs[]">
                                <label for="patro_{$f.patro}">{$f.patro} ({$f.count})</label>
                            </div>
                        {/foreach}
                    </div>
                </div>
                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Výtah</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $elevator as $f}
                            {if $f.vytah eq 1}
                                {$f.vytah = "Ano"}
                            {elseif $f.vytah eq "0"}
                                {$f.vytah = "Ne"}
                            {else}
                                {$f.vytah = "Neuvedeno"}
                            {/if}
                            <div class="half">
                                <input type="checkbox" id="vytah_{$f.vytah}" value="{$f.vytah}" {if isset($filters['elevator']) && in_array($f['vytah'], $filters['elevator'])}checked{/if} name="elevator[]">
                                <label for="vytah_{$f.vytah}">{$f.vytah} ({$f.count})</label>
                                {if isset($filters['elevator']) && in_array(rawurlencode($f['vytah']), ($filters['elevator']))}
                                {/if}

                            </div>
                        {/foreach}
                    </div>
                </div>

                                <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Balkon</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $balcony as $f}
                            {if $f.balkon eq 1}
                                {$f.balkon = "Ano"}
                            {elseif $f.balkon eq "0"}
                                {$f.balkon = "Ne"}
                            {else}
                                {$f.balkon = "Neuvedeno"}
                            {/if}
                            <div class="half">
                                <input type="checkbox" id="balkon_{$f.balkon}" value="{$f.balkon}" {if isset($filters['balcony']) && in_array($f['balkon'], $filters['balcony'])}checked{/if} name="balcony[]">
                                <label for="balkon_{$f.balkon}">{$f.balkon} ({$f.count})</label>
                                {if isset($filters['balcony']) && in_array(rawurlencode($f['balkon']), ($filters['balcony']))}
                                {/if}

                            </div>
                        {/foreach}
                    </div>
                </div>

                 <div href="#" class="list-group-item list-group-item-action py-3 lh-sm">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <strong class="mb-1">Zvířata</strong>
                    </div>
                    <div class="col-10 mb-1">
                        {foreach $animals as $f}
                            {if $f.zvirata eq 1}
                                {$f.zvirata = "Ano"}
                            {elseif $f.zvirata eq "0"}
                                {$f.zvirata = "Ne"}
                            {else}
                                {$f.zvirata = "Neuvedeno"}
                            {/if}
                            <div class="half">
                                <input type="checkbox" id="animal_{$f.zvirata}" value="{rawurlencode($f.zvirata)}" {if isset($filters['animals']) && in_array($f['zvirata'], $filters['animals'])}checked{/if} name="animals[]">
                                <label for="animal_{$f.zvirata}">{$f.zvirata} ({$f.count})</label>
                            </div>
                        {/foreach}
                    </div>
                </div>
                <input class="btn btn-secondary" type="submit" value="Potvrdit">
            </div>
        </form>
    </div>
</div>