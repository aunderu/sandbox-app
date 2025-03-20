@if($record->video_url)
    <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-md border border-gray-200">
        <iframe 
            class="w-full h-full"
            src="https://www.youtube.com/embed/{{ \Str::afterLast($record->video_url, 'v=') }}" 
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
    </div>
@endif
