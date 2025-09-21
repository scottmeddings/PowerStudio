<?php
// app/Http/Requests/SocialPostRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialPostRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'title'        => ['nullable','string','max:160'],
            'body'         => ['required','string','max:4000'],
            'episode_url'  => ['nullable','url','max:2000'],
            'visibility'   => ['required','in:public,connections,private'],
            'services'     => ['required','array','min:1'],
            'services.*'   => ['in:x,linkedin,facebook,instagram,threads,youtube,tiktok'],

            'images'       => ['nullable','array','max:8'],
            'images.*'     => ['image','mimes:jpeg,png,webp','max:5120'], // 5 MB each

            'video'        => ['nullable','file','mimetypes:video/mp4,video/quicktime,video/webm','max:524288'], // 512 MB
        ];
    }
}
