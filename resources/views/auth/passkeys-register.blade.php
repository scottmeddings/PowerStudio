@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container py-5">
  <h2 class="mb-4">Register a Passkey</h2>
  <button id="btn-create" class="btn btn-primary btn-lg">Create Passkey</button>
  <pre id="debug" class="mt-4 small bg-light p-3 rounded" style="white-space:pre-wrap"></pre>
</div>

<script>
const debug = msg => {
  const el = document.getElementById('debug');
  el.textContent += (typeof msg === 'string' ? msg : JSON.stringify(msg, null, 2)) + '\n';
  console.log(msg);
};

const b64urlToBuf = s =>
  Uint8Array.from(atob(s.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));

const hexToBuf = hex =>
  Uint8Array.from((hex.match(/.{1,2}/g) || []).map(b => parseInt(b, 16)));

function toBuf(v){
  if (v == null) throw new Error('bad_options_shape: missing binary');
  if (typeof v !== 'string') return new Uint8Array(v);
  if (/^[0-9a-f]+$/i.test(v) && v.length % 2 === 0) return hexToBuf(v); // hex
  return b64urlToBuf(v); // base64url
}

// Accepts {publicKey:{...}} or flat {...}
function normalizeAttestationOptions(json){
  const pk = json?.publicKey ?? json;
  if (!pk?.challenge || !pk?.user?.id) throw new Error('bad_options_shape: no challenge/user.id');

  const out = { ...pk };
  out.challenge = toBuf(pk.challenge);
  out.user = { ...pk.user, id: toBuf(pk.user.id) };
  out.excludeCredentials = (pk.excludeCredentials || []).map(c => ({ ...c, id: toBuf(c.id) }));
  return { publicKey: out };
}

document.getElementById('btn-create')?.addEventListener('click', async () => {
  try {
    if (!('PublicKeyCredential' in window)) {
      throw new Error('WebAuthn not supported in this browser');
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // 1) OPTIONS
    const o = await fetch('{{ route('passkeys.register.options') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    debug(['options status', o.status]);
    if (!o.ok) throw new Error('options_http_' + o.status + ' ' + (await o.text()).slice(0,500));

    const json = await o.json();
    debug(['options payload', json]);

    // 2) Normalize + convert binaries
    const { publicKey } = normalizeAttestationOptions(json);

    // 3) Windows Hello / platform creation
    const cred = await navigator.credentials.create({ publicKey });
    debug(['created credential', !!cred]);
    if (!cred) throw new Error('create_cancelled');

    // 4) POST to store
    const bufToB64url = b => {
      let bin=''; new Uint8Array(b).forEach(v => bin += String.fromCharCode(v));
      return btoa(bin).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
    };

    const payload = {
      id: cred.id,
      rawId: bufToB64url(cred.rawId),
      type: cred.type,
      response: {
        clientDataJSON:    bufToB64url(cred.response.clientDataJSON),
        attestationObject: bufToB64url(cred.response.attestationObject)
      }
    };

    const s = await fetch('{{ route('passkeys.register') }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(payload)
    });
    const body = await s.text();
    debug(['store status', s.status, body]);
    if (!s.ok) throw new Error('store_http_' + s.status + ' ' + body.slice(0,500));

    alert('Passkey created!');
    location.reload();
  } catch (e) {
    console.error(e);
    debug(['error', e && (e.message || e.name || e)]);
    alert('Passkey registration failed: ' + (e.message || e.name || 'unknown'));
  }
});
</script>
@endsection
