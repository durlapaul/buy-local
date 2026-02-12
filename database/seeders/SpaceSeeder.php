<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
{
    public function run(): void
    {
        $owner1 = User::where('email', 'john@example.com')->first();
        $owner2 = User::where('email', 'maria@example.com')->first();
        $owner3 = User::where('email', 'david@example.com')->first();

        $worker1 = User::where('email', 'sarah@example.com')->first();
        $worker2 = User::where('email', 'mike@example.com')->first();
        $worker3 = User::where('email', 'ana@example.com')->first();

        // Cluj-Napoca spaces
        $space1 = Space::create([
            'owner_id' => $owner1->id,
            'name' => 'Downtown Tennis Club',
            'description' => 'Premium tennis courts in the heart of Cluj-Napoca',
            'address' => 'Strada Avram Iancu 3',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
            'contact_email' => 'info@downtowntennis.ro',
            'contact_phone' => '+40 264 123 456',
            'latitude' => 46.7712,
            'longitude' => 23.6236,
            'is_active' => true,
        ]);

        $space2 = Space::create([
            'owner_id' => $owner1->id,
            'name' => 'Riverside Sports Complex',
            'description' => 'Multi-sport facility with tennis, basketball, and volleyball courts near Someș River',
            'address' => 'Calea Dorobanților 89',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
            'contact_email' => 'contact@riverside-sports.ro',
            'contact_phone' => '+40 264 234 567',
            'latitude' => 46.7833,
            'longitude' => 23.6167,
            'is_active' => true,
        ]);

        $space3 = Space::create([
            'owner_id' => $owner1->id,
            'name' => 'Mănăștur Arena',
            'description' => 'Modern sports complex in Mănăștur neighborhood',
            'address' => 'Strada Primăverii 15',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
            'contact_email' => 'booking@manastur-arena.ro',
            'contact_phone' => '+40 264 345 678',
            'latitude' => 46.7505,
            'longitude' => 23.5547,
            'is_active' => true,
        ]);

        // București spaces
        $space4 = Space::create([
            'owner_id' => $owner2->id,
            'name' => 'Elite Fitness & Courts București',
            'description' => 'Modern fitness center with indoor courts in the capital',
            'address' => 'Bulevardul Unirii 45',
            'city' => 'București',
            'country' => 'Romania',
            'contact_email' => 'bucuresti@elitefitness.ro',
            'contact_phone' => '+40 21 555 1234',
            'latitude' => 44.4268,
            'longitude' => 26.1025,
            'is_active' => true,
        ]);

        $space5 = Space::create([
            'owner_id' => $owner2->id,
            'name' => 'Herăstrău Park Sports Center',
            'description' => 'Outdoor sports facility in beautiful Herăstrău Park',
            'address' => 'Șoseaua Nordului 7-9',
            'city' => 'București',
            'country' => 'Romania',
            'contact_email' => 'herastrau@sportscenter.ro',
            'contact_phone' => '+40 21 666 2345',
            'latitude' => 44.4758,
            'longitude' => 26.0819,
            'is_active' => true,
        ]);

        $space6 = Space::create([
            'owner_id' => $owner2->id,
            'name' => 'Pipera Tennis Academy',
            'description' => 'Professional tennis courts and training facility',
            'address' => 'Strada Dimitrie Pompei 10',
            'city' => 'București',
            'country' => 'Romania',
            'contact_email' => 'academy@piperatennis.ro',
            'contact_phone' => '+40 21 777 3456',
            'latitude' => 44.4950,
            'longitude' => 26.1203,
            'is_active' => true,
        ]);

        // Satu Mare spaces
        $space7 = Space::create([
            'owner_id' => $owner3->id,
            'name' => 'Satu Mare Sports Arena',
            'description' => 'Indoor sports arena in the city center',
            'address' => 'Strada Mihai Viteazu 12',
            'city' => 'Satu Mare',
            'country' => 'Romania',
            'contact_email' => 'info@sm-arena.ro',
            'contact_phone' => '+40 261 123 789',
            'latitude' => 47.7926,
            'longitude' => 22.8856,
            'is_active' => true,
        ]);

        $space8 = Space::create([
            'owner_id' => $owner3->id,
            'name' => 'Garden Sports Complex',
            'description' => 'Outdoor courts surrounded by greenery',
            'address' => 'Strada Grădinii Publice 5',
            'city' => 'Satu Mare',
            'country' => 'Romania',
            'contact_email' => 'contact@garden-sports.ro',
            'contact_phone' => '+40 261 234 890',
            'latitude' => 47.7897,
            'longitude' => 22.8775,
            'is_active' => true,
        ]);

        // Inactive space
        $space9 = Space::create([
            'owner_id' => $owner1->id,
            'name' => 'Old Town Courts (Closed for Renovation)',
            'description' => 'Temporarily closed for upgrades',
            'address' => 'Strada Memorandumului 28',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
            'contact_email' => 'renovation@oldtowncourts.ro',
            'contact_phone' => '+40 264 999 000',
            'latitude' => 46.7693,
            'longitude' => 23.5908,
            'is_active' => false,
        ]);

        
        // Assign workers to spaces
        $space1->users()->attach($worker1->id, ['role' => 'space_admin']);
        $space1->users()->attach($worker2->id, ['role' => 'space_worker']);

        $space2->users()->attach($worker2->id, ['role' => 'space_admin']);

        $space4->users()->attach($worker3->id, ['role' => 'space_worker']);

        $space5->users()->attach($worker1->id, ['role' => 'space_worker']);
        $space5->users()->attach($worker3->id, ['role' => 'space_admin']);

        $space7->users()->attach($worker2->id, ['role' => 'space_admin']);
        $space7->users()->attach($worker1->id, ['role' => 'space_worker']);
    }
}