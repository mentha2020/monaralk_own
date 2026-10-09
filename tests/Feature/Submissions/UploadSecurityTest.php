<?php

use App\Livewire\SubmitVehicleWizard;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('the wizard rejects a php script wearing a jpg extension', function () {
    $file = UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "pwned";');
    $file->mimeType('text/x-php');

    Livewire::test(SubmitVehicleWizard::class)
        ->set('step', 3)
        ->set('photos', [$file])
        ->call('next')
        ->assertHasErrors('photos.0');
});

test('the wizard rejects a photo over the size limit', function () {
    $canvas = imagecreatetruecolor(64, 64);
    ob_start();
    imagejpeg($canvas, null, 90);
    $image = ob_get_clean();
    imagedestroy($canvas);

    $blob = $image.str_repeat('A', 6 * 1024 * 1024);

    Livewire::test(SubmitVehicleWizard::class)
        ->set('step', 3)
        ->set('photos', [UploadedFile::fake()->createWithContent('huge.jpg', $blob)])
        ->call('next')
        ->assertHasErrors('photos.0');
});

test('the wizard accepts a real photo', function () {
    Livewire::test(SubmitVehicleWizard::class)
        ->set('step', 3)
        ->set('photos', [UploadedFile::fake()->image('car.jpg', 800, 600)])
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 4);
});
