<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RF-15: página «Acerca de nosotros». */
class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_who_we_are_how_we_work_and_how_to_start(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>Acerca de nosotros · Aprende Guate</title>', false)
            ->assertSeeInOrder(['Quiénes somos', 'Lo que nos guía', 'Cómo trabajamos', 'La plataforma por dentro', 'Quién está detrás'])
            ->assertSee(route('register'), false)
            ->assertDontSee('Ir a mi panel');
    }

    public function test_signed_in_people_are_sent_to_their_panel_instead_of_registering(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('about'))
            ->assertOk()
            ->assertSee('Ir a mi panel')
            ->assertDontSee('Crear mi cuenta gratis');
    }

    public function test_the_page_is_linked_from_the_home_page_and_the_footer(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // Barra superior y pie de página.
        $this->assertSame(2, substr_count($html, 'href="'.route('about').'"'));
        $this->get(route('legal.faq'))->assertSee('href="'.route('about').'"', false);
    }

    public function test_every_gallery_image_exists(): void
    {
        preg_match_all('#images/acerca/([a-z-]+\.webp)#', $this->get(route('about'))->getContent(), $matches);

        $this->assertCount(4, array_unique($matches[1]));
        foreach (array_unique($matches[1]) as $file) {
            $this->assertFileExists(public_path('images/acerca/'.$file));
        }
    }
}
