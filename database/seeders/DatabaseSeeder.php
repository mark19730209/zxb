<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $otherUsers = User::factory(3)->create();
        $allUsers = collect([$user, ...$otherUsers]);

        $organizations = Organization::factory(15)->create();

        // Create contacts — some with organizations, some without
        $contacts = Contact::factory(75)
            ->recycle($organizations)
            ->create();

        $contactsWithoutOrg = Contact::factory(25)
            ->withoutOrganization()
            ->create();

        $allContacts = $contacts->merge($contactsWithoutOrg);

        // Create notes spread across contacts by various users
        $allContacts->random(40)->each(function (Contact $contact) use ($allUsers) {
            Note::factory(rand(1, 5))
                ->recycle($allUsers)
                ->for($contact)
                ->create();
        });
    }
}
