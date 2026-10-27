/* Interface de pagamentos: nenhum Access Token ou dado de cartão passa por este arquivo. */
'use strict';
window.SportZonePayments = function ({api,esc,money,config}) {
 const paymentLabels={pendente:'Pendente',aprovado_teste:'Aprovado na simulação',cancelado_teste:'Cancelado na simulação',not_created:'Aguardando início do pagamento',creating:'Processando solicitação',creation_unknown:'Resposta incerta · consulte antes de repetir',creation_failed:'Criação não autorizada',pending:'Aguardando pagamento',in_process:'Em análise',authorized:'Autorizado, aguardando captura',approved:'Pagamento aprovado',rejected:'Pagamento recusado',cancelled:'Pagamento cancelado',refunded:'Pagamento reembolsado',charged_back:'Pagamento contestado'};
 const methodLabels={pix:'PIX',cartao:'Cartão de crédito',boleto:'Boleto bancário'};
 let timer=null,polls=0;
 const environment=c=>c.environment==='production'?'Produção · cobrança real':c.environment==='sandbox'?'Sandbox Mercado Pago':'Simulação local';
 const local=config.provider==='simulation';
 function decorate(){
  const bar=document.querySelector('.topbar span:last-child');if(bar){bar.textContent=environment(config);bar.classList.toggle('live-environment',config.real);}
  const footer=document.querySelector('.footer-note p');if(footer)footer.textContent=local?'Projeto escolar. Pagamentos simulados, sem cobrança. Produtos de teste sem entrega física.':`${environment(config)}. Confirmação verificada na API do Mercado Pago. Frete dos artigos esportivos estimado; itens de teste sem entrega física.`;
  document.querySelectorAll('[data-environment]').forEach(el=>el.textContent=environment(config));
 }
 async function checkout(){
  const response=await api('/payment/options');const c=response.config;
  return `<div class="cart-layout"><section class="panel payment-checkout"><span class="eyebrow">${esc(environment(c))}</span><h2>${local?'Pagamento de teste':'Como você quer pagar?'}</h2><p class="notice ${c.real?'notice-live':''}">${local?'Nenhuma cobrança será feita. O pedido será criado aguardando pagamento simulado.':c.real?'Ao confirmar, você autoriza a criação de uma cobrança real pelo valor revisado. O pedido só será marcado como pago após consulta à API do Mercado Pago.':'Ambiente de testes do Mercado Pago. Utilize as contas e os dados de teste da sua aplicação.'}</p>${!c.ready?`<div class="configuration-warning" role="alert"><strong>Configuração pendente</strong><p>${esc(c.missing.join(' · '))}</p><p>As credenciais devem ser configuradas no servidor. Nenhuma cobrança será criada.</p></div>`:''}<form data-form="checkout"><div class="payment-options">${Object.entries(methodLabels).map(([key,label])=>{const o=response.methods[key]||{};return `<label class="${o.available?'':'method-unavailable'}"><input type="radio" name="method" value="${key}" aria-label="${label}" required ${o.available?'':'disabled'}><i aria-hidden="true" class="bi bi-${key==='pix'?'qr-code':key==='cartao'?'credit-card':'upc'}"></i><span>${label}<small>${local?'Simulação local':key==='pix'?'QR Code e código copia e cola':'Pagamento no ambiente seguro do Mercado Pago'}${!o.available?' · '+esc(o.reason||'Indisponível')+(o.minimum_cents!==null&&o.minimum_cents!==undefined?' · mínimo '+money(o.minimum_cents):''):''}</small></span></label>`;}).join('')}</div>${local?'':`<label class="field pix-document" hidden>CPF do pagador<input name="cpf" inputmode="numeric" autocomplete="off" maxlength="14" placeholder="000.000.000-00" disabled><small>Enviado ao Mercado Pago para gerar o PIX; não guardamos o CPF no banco.</small></label>`}<label class="check-label"><input type="checkbox" name="ack" required> ${local?'Entendo que esta é uma compra de teste, sem cobrança.':c.real?'Autorizo a criação desta cobrança real, no valor exibido no resumo.':'Confirmo que utilizarei o ambiente sandbox do Mercado Pago.'}</label><button class="button full" ${!c.ready?'disabled':''}>${local?'Criar pedido de teste':c.real?'Confirmar e iniciar pagamento real':'Iniciar pagamento no sandbox'} <i aria-hidden="true" class="bi bi-arrow-right"></i></button><p class="form-error" role="alert"></p></form><p class="payment-security"><i aria-hidden="true" class="bi bi-lock"></i> ${local?'Não informe dados bancários.':'Cartão e boleto são preenchidos diretamente no Mercado Pago. O SportZone não recebe número de cartão nem CVV.'}</p><button class="text-button" type="button" data-action="back-review">Voltar à revisão</button></section><aside class="panel summary-panel" id="paymentOrderSummary"></aside></div>`;
 }
 document.addEventListener('change',e=>{
  if(e.target.name!=='method')return;
  const field=document.querySelector('.pix-document'),input=field?.querySelector('input');if(!field)return;
  const pix=e.target.value==='pix';field.hidden=!pix;input.disabled=!pix;input.required=pix;
 });
 function card(p,id,admin){
  const actual=p.provider==='mercadopago',pending=['not_created','creation_unknown','creation_failed'].includes(p.status);
  const message=!actual?(p.status==='aprovado_teste'?'Aprovação simulada registrada. Nenhuma cobrança real.':p.status==='cancelado_teste'?'Pedido cancelado na simulação. Nenhuma cobrança real.':'Aguardando sua ação de simulação; nenhuma cobrança foi criada.'):(p.verified_at?'Última confirmação da API: '+esc(p.verified_at)+' UTC.':'Pagamento ainda não confirmado.');
  return `<section class="gateway-panel panel" id="gatewayPanel" aria-label="Pagamento do pedido"><div class="gateway-heading"><i aria-hidden="true" class="bi bi-${['approved','aprovado_teste'].includes(p.status)?'check-circle-fill':'shield-check'}"></i><div><span class="eyebrow">${esc(environment(p))}</span><h2>${esc(paymentLabels[p.status]||p.status)}</h2></div></div><p id="gatewayFeedback" role="status">${message}</p>${p.needs_review?'<p class="configuration-warning">Esta transação precisa de revisão administrativa.</p>':''}<p class="small muted">${actual?'ID Mercado Pago: '+esc(p.remote_id||'ainda não atribuído'):'Referência de simulação: '+esc(p.reference)}${actual?' · rascunho de integração desabilitado nesta entrega.':' · não é um código pagável.'}</p></section>`;
 }
 function mount(order,admin,onRefresh){
  clearInterval(timer);polls=0;const p=order.payment;if(!p)return;
  const root=admin?document.querySelector('#adminContent'):document.querySelector('#main');
  const banner=root.querySelector('.order-banner>span:last-child');if(banner)banner.textContent=`${methodLabels[p.method]} · ${paymentLabels[p.status]||p.status} · ${environment(p)}`;
  root.querySelector('.summary-panel .notice')?.remove();
  if(p.provider==='mercadopago'){
   root.querySelectorAll('[data-order-status]').forEach(el=>el.remove());
   const select=root.querySelector('[data-form="admin-status"] select');if(select){[...select.options].filter(o=>['pagamento_aprovado','cancelado'].includes(o.value)).forEach(o=>o.remove());if(select.options.length===1)select.form.querySelector('button').disabled=true;}
  }
  const section=root.querySelector('.cart-layout>section');section.insertAdjacentHTML('afterbegin',card(p,order.id,admin));
  root.querySelector('[data-copy-pix]')?.addEventListener('click',async()=>{const text=root.querySelector('#pixCopyCode');try{await navigator.clipboard.writeText(text.value);root.querySelector('#gatewayFeedback').textContent='Código PIX copiado.';}catch{text.select();root.querySelector('#gatewayFeedback').textContent='Selecione e copie o código exibido.';}});
  const refresh=async()=>{const feedback=root.querySelector('#gatewayFeedback');try{const r=await api('/payment/refresh',{id:order.id});if(r.status!==p.status || r.order_status!==order.status || r.transactions.length!==p.transactions.length){clearInterval(timer);await onRefresh();}else if(feedback)feedback.textContent='Status consultado na API. Aguardando atualização do pagamento.';}catch(e){if(feedback)feedback.textContent=e.message;}};
  root.querySelector('[data-payment-refresh]')?.addEventListener('click',refresh);
  root.querySelector('[data-payment-cancel]')?.addEventListener('click',async e=>{e.target.disabled=true;try{await api('/payment/cancel',{id:order.id});await onRefresh();}catch(err){root.querySelector('#gatewayFeedback').textContent=err.message;e.target.disabled=false;}});
  const resume=root.querySelector('[data-form="resume-payment"]');resume?.addEventListener('submit',async e=>{e.preventDefault();const b=resume.querySelector('button');b.disabled=true;const d=Object.fromEntries(new FormData(resume));try{await api('/payment/start',{id:order.id,cpf:d.cpf,confirm_live_payment:d.ack==='on'});await onRefresh();}catch(err){resume.querySelector('.form-error').textContent=err.message;b.disabled=false;}});
  if(!admin&&p.provider==='mercadopago'&&['pending','in_process','authorized','creating','creation_unknown'].includes(p.status))timer=setInterval(()=>{if(!section.isConnected||++polls>45){clearInterval(timer);return;}if(!document.hidden)refresh();},8000);
 }
 return {decorate,checkout,mount,environment,paymentLabels,local,config};
};

