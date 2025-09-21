<button id="btn-create-passkey" class="btn btn-primary">Create Passkey</button>
<ul id="passkey-list" class="mt-3"></ul>

<script>
async function b64urlToBuf(s){return Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')),c=>c.charCodeAt(0));}
async function bufToB64url(b){let bin=''; new Uint8Array(b).forEach(v=>bin+=String.fromCharCode(v)); return btoa(bin).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');}

document.getElementById('btn-create-passkey')?.addEventListener('click', async () => {
  try {
    // 1) get creation options
    const r1 = await fetch('{{ route('passkeys.register.options') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'} });
    if (!r1.ok) throw new Error('options_failed');
    const opts = await r1.json();

    // 2) convert to binary
    opts.publicKey.challenge = await b64urlToBuf(opts.publicKey.challenge);
    opts.publicKey.user.id   = await b64urlToBuf(opts.publicKey.user.id);
    if (Array.isArray(opts.publicKey.excludeCredentials)) {
      opts.publicKey.excludeCredentials = opts.publicKey.excludeCredentials.map(c => ({...c, id: b64urlToBuf(c.id)}));
    }

    // 3) create credential
    const cred = await navigator.credentials.create({ publicKey: opts.publicKey });

    // 4) send to server
    const payload = {
      id: cred.id,
      rawId: await bufToB64url(cred.rawId),
      type: cred.type,
      response: {
        clientDataJSON:    await bufToB64url(cred.response.clientDataJSON),
        attestationObject: cred.response.attestationObject ? await bufToB64url(cred.response.attestationObject) : null,
      },
      transports: (cred.response.getTransports ? cred.response.getTransports().join(',') : ''),
      label: navigator.userAgentData?.platform || navigator.platform || 'Passkey',
    };

    const r2 = await fetch('{{ route('passkeys.register') }}', {
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
      body: JSON.stringify(payload)
    });
    if (!r2.ok) throw new Error('store_failed');

    alert('Passkey created! You can now sign in with Passkey on this device.');
  } catch (e) {
    console.error(e);
    alert('Passkey registration failed: ' + (e.name || e.message || 'unknown'));
  }
});
</script>
