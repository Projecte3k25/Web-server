<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Continent;
use App\Models\Pais;
use App\Models\Frontera;
use App\Models\Estat;
use App\Models\Usuari;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $estats = [
            ["id" => 1, "nom" => "Espera"],
            ["id" => 2, "nom" => "Colocacio"],
            ["id" => 3, "nom" => "Reforç"],
            ["id" => 4, "nom" => "ReforçTropes"],
            ["id" => 5, "nom" => "Atac"],
            ["id" => 6, "nom" => "Recolocacio"],
            ["id" => 7, "nom" => "Final"],
            ["id" => 8, "nom" => "Oberta"],
        ];

        foreach ($estats as $estat) {
            Estat::create($estat);
        }
        $bot = Usuari::create(["nom" => "Bot", "login" => "bot", "password" => "", "avatar" => "/media/avatars/bot.png"]);
        $bot->update(["id" => 0]);
        DB::statement("ALTER TABLE usuaris AUTO_INCREMENT = 1");


        $usuaris = [
            ["nom" => "Kevin", "login" => "kevin", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/bot.png", "wins" => 10, "games" => 15, "elo" => 100],
            ["nom" => "Test", "login" => "test", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 5, "games" => 10, "elo" => 50],
            ["nom" => "Marc", "login" => "marc", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 8, "games" => 14, "elo" => 80],
            ["nom" => "Admin", "login" => "admin", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 12, "games" => 20, "elo" => 120],

            ["nom" => "Laura", "login" => "laura", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 7, "games" => 10, "elo" => 70],
            ["nom" => "Oscar", "login" => "oscar", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 6, "games" => 9, "elo" => 60],
            ["nom" => "Julia", "login" => "julia", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 9, "games" => 12, "elo" => 90],
            ["nom" => "Alex", "login" => "alex", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 4, "games" => 10, "elo" => 40],
            ["nom" => "Nina", "login" => "nina", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 13, "games" => 15, "elo" => 130],
            ["nom" => "Pol", "login" => "pol", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 5, "games" => 6, "elo" => 50],
            ["nom" => "Sara", "login" => "sara", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 2, "games" => 5, "elo" => 20],
            ["nom" => "David", "login" => "david", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 11, "games" => 13, "elo" => 110],
            ["nom" => "Eva", "login" => "eva", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 3, "games" => 8, "elo" => 30],
            ["nom" => "Roger", "login" => "roger", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 15, "games" => 20, "elo" => 150],
            ["nom" => "Clara", "login" => "clara", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 8, "games" => 11, "elo" => 80],
            ["nom" => "Gerard", "login" => "gerard", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 10, "games" => 13, "elo" => 100],
            ["nom" => "Irene", "login" => "irene", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 9, "games" => 12, "elo" => 90],
            ["nom" => "Toni", "login" => "toni", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 6, "games" => 9, "elo" => 60],
            ["nom" => "Marta", "login" => "marta", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 14, "games" => 18, "elo" => 140],
            ["nom" => "Joan", "login" => "joan", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 7, "games" => 9, "elo" => 70],
            ["nom" => "Helena", "login" => "helena", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 12, "games" => 14, "elo" => 120],
            ["nom" => "Albert", "login" => "albert", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 3, "games" => 7, "elo" => 30],
            ["nom" => "Paula", "login" => "paula", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 1, "games" => 5, "elo" => 10],
            ["nom" => "Arnau", "login" => "arnau", "password" => "81dc9bdb52d04dc20036dbd8313ed055", "avatar" => "/media/avatars/avatar.png", "wins" => 0, "games" => 3, "elo" => 0],
        ];

        foreach ($usuaris as $usuari) {
            Usuari::create($usuari);
        }

        $continents = [
            ['nom' => 'Nort_America', 'reforc_tropes' => 5],
            ['nom' => 'Sud_America', 'reforc_tropes' => 2],
            ['nom' => 'Europa', 'reforc_tropes' => 5],
            ['nom' => 'Africa', 'reforc_tropes' => 3],
            ['nom' => 'Asia', 'reforc_tropes' => 7],
            ['nom' => 'Oceania', 'reforc_tropes' => 2],
        ];

        foreach ($continents as $continent) {
            Continent::create($continent);
        }

        $territories = [
            // América del Norte
            ['nom' => 'ALASKA', 'continent' => 'Nort_America'],
            ['nom' => 'ALBERTA', 'continent' => 'Nort_America'],
            ['nom' => 'CARIBBEAN', 'continent' => 'Nort_America'],
            ['nom' => 'NORTHWEST', 'continent' => 'Nort_America'],
            ['nom' => 'GREENLAND', 'continent' => 'Nort_America'],
            ['nom' => 'USA_WEST', 'continent' => 'Nort_America'],
            ['nom' => 'ONTARIO', 'continent' => 'Nort_America'],
            ['nom' => 'QUEBEC', 'continent' => 'Nort_America'],
            ['nom' => 'EAST_US', 'continent' => 'Nort_America'],

            // América del Sur
            ['nom' => 'ARGENTINA', 'continent' => 'Sud_America'],
            ['nom' => 'BRAZIL', 'continent' => 'Sud_America'],
            ['nom' => 'PERU', 'continent' => 'Sud_America'],
            ['nom' => 'VENEZUELA', 'continent' => 'Sud_America'],

            // Europa
            ['nom' => 'GREAT_BRITAIN', 'continent' => 'Europa'],
            ['nom' => 'ICELAND', 'continent' => 'Europa'],
            ['nom' => 'NORTH_EU', 'continent' => 'Europa'],
            ['nom' => 'ESCANDINAVIA', 'continent' => 'Europa'],
            ['nom' => 'EU_SOUTH', 'continent' => 'Europa'],
            ['nom' => 'UKRAINE', 'continent' => 'Europa'],
            ['nom' => 'EU_WEST', 'continent' => 'Europa'],

            // África
            ['nom' => 'CONGO', 'continent' => 'Africa'],
            ['nom' => 'EAST_AFRICA', 'continent' => 'Africa'],
            ['nom' => 'EGYPT', 'continent' => 'Africa'],
            ['nom' => 'MADAGASCAR', 'continent' => 'Africa'],
            ['nom' => 'NORTH_AFRICA', 'continent' => 'Africa'],
            ['nom' => 'SOUTH_AFRICA', 'continent' => 'Africa'],

            // Asia
            ['nom' => 'AFGHANISTAN', 'continent' => 'Asia'],
            ['nom' => 'CHINA', 'continent' => 'Asia'],
            ['nom' => 'INDIA', 'continent' => 'Asia'],
            ['nom' => 'IRKUTSK', 'continent' => 'Asia'],
            ['nom' => 'JAPAN', 'continent' => 'Asia'],
            ['nom' => 'KAMCHATKA', 'continent' => 'Asia'],
            ['nom' => 'MIDDLE_EAST', 'continent' => 'Asia'],
            ['nom' => 'MONGOLIA', 'continent' => 'Asia'],
            ['nom' => 'SIAM', 'continent' => 'Asia'],
            ['nom' => 'SIBERIA', 'continent' => 'Asia'],
            ['nom' => 'URAL', 'continent' => 'Asia'],
            ['nom' => 'YAKUTSK', 'continent' => 'Asia'],

            // Oceanía
            ['nom' => 'EAST_AUSTRAL', 'continent' => 'Oceania'],
            ['nom' => 'INDONESIA', 'continent' => 'Oceania'],
            ['nom' => 'NEW_GUINEA', 'continent' => 'Oceania'],
            ['nom' => 'WEST_AUSTRALIA', 'continent' => 'Oceania'],
        ];

        foreach ($territories as $territory) {
            $continent = Continent::where('nom', $territory['continent'])->first();

            if ($continent) {
                Pais::create([
                    'nom' => $territory['nom'],
                    'continent_id' => $continent->id,
                    'imatge' => 'default.png',
                ]);
            } else {
                echo "ERROR!!!Continente no encontrado: {$territory['continent']}\n";
            }
        }

        $fronteras = [
            ['ALASKA', 'ALBERTA'],
            ['ALASKA', 'NORTHWEST'],
            ['ALASKA', 'KAMCHATKA'],
            ['ALBERTA', 'NORTHWEST'],
            ['ALBERTA', 'ONTARIO'],
            ['ALBERTA', 'USA_WEST'],
            ['ALBERTA', 'ALASKA'],
            ['CARIBBEAN', 'EAST_US'],
            ['CARIBBEAN', 'USA_WEST'],
            ['CARIBBEAN', 'VENEZUELA'],
            ['NORTHWEST', 'ONTARIO'],
            ['NORTHWEST', 'GREENLAND'],
            ['NORTHWEST', 'ALASKA'],
            ['NORTHWEST', 'ALBERTA'],
            ['GREENLAND', 'ONTARIO'],
            ['GREENLAND', 'QUEBEC'],
            ['GREENLAND', 'ICELAND'],
            ['GREENLAND', 'NORTHWEST'],
            ['USA_WEST', 'ONTARIO'],
            ['USA_WEST', 'CARIBBEAN'],
            ['USA_WEST', 'ALBERTA'],
            ['USA_WEST', 'EAST_US'],
            ['ONTARIO', 'QUEBEC'],
            ['ONTARIO', 'EAST_US'],
            ['ONTARIO', 'USA_WEST'],
            ['ONTARIO', 'ALBERTA'],
            ['ONTARIO', 'NORTHWEST'],
            ['QUEBEC', 'EAST_US'],
            ['QUEBEC', 'ONTARIO'],
            ['QUEBEC', 'GREENLAND'],
            ['ARGENTINA', 'BRAZIL'],
            ['ARGENTINA', 'PERU'],
            ['BRAZIL', 'PERU'],
            ['BRAZIL', 'VENEZUELA'],
            ['BRAZIL', 'NORTH_AFRICA'],
            ['PERU', 'VENEZUELA'],
            ['PERU', 'BRAZIL'],
            ['PERU', 'ARGENTINA'],
            ['VENEZUELA', 'CARIBBEAN'],
            ['VENEZUELA', 'BRAZIL'],
            ['VENEZUELA', 'PERU'],
            ['GREAT_BRITAIN', 'ICELAND'],
            ['GREAT_BRITAIN', 'ESCANDINAVIA'],
            ['GREAT_BRITAIN', 'EU_WEST'],
            ['GREAT_BRITAIN', 'NORTH_EU'],
            ['ICELAND', 'ESCANDINAVIA'],
            ['ICELAND', 'GREENLAND'],
            ['ICELAND', 'GREAT_BRITAIN'],
            ['NORTH_EU', 'ESCANDINAVIA'],
            ['NORTH_EU', 'UKRAINE'],
            ['NORTH_EU', 'EU_WEST'],
            ['NORTH_EU', 'EU_SOUTH'],
            ['NORTH_EU', 'GREAT_BRITAIN'],
            ['ESCANDINAVIA', 'UKRAINE'],
            ['ESCANDINAVIA', 'NORTH_EU'],
            ['ESCANDINAVIA', 'GREAT_BRITAIN'],
            ['EU_SOUTH', 'EU_WEST'],
            ['EU_SOUTH', 'UKRAINE'],
            ['EU_SOUTH', 'NORTH_AFRICA'],
            ['EU_SOUTH', 'EGYPT'],
            ['EU_SOUTH', 'MIDDLE_EAST'],
            ['UKRAINE', 'URAL'],
            ['UKRAINE', 'AFGHANISTAN'],
            ['UKRAINE', 'MIDDLE_EAST'],
            ['UKRAINE', 'ESCANDINAVIA'],
            ['UKRAINE', 'NORTH_EU'],
            ['UKRAINE', 'EU_SOUTH'],
            ['EU_WEST', 'NORTH_AFRICA'],
            ['EU_WEST', 'GREAT_BRITAIN'],
            ['EU_WEST', 'NORTH_EU'],
            ['EU_WEST', 'EU_SOUTH'],
            ['CONGO', 'EAST_AFRICA'],
            ['CONGO', 'NORTH_AFRICA'],
            ['CONGO', 'SOUTH_AFRICA'],
            ['EAST_AFRICA', 'EGYPT'],
            ['EAST_AFRICA', 'CONGO'],
            ['EAST_AFRICA', 'MADAGASCAR'],
            ['EAST_AFRICA', 'NORTH_AFRICA'],
            ['EAST_AFRICA', 'SOUTH_AFRICA'],
            ['EAST_AFRICA', 'MIDDLE_EAST'],
            ['EGYPT', 'NORTH_AFRICA'],
            ['EGYPT', 'MIDDLE_EAST'],
            ['EGYPT', 'EU_SOUTH'],
            ['MADAGASCAR', 'SOUTH_AFRICA'],
            ['MADAGASCAR', 'EAST_AFRICA'],
            ['NORTH_AFRICA', 'EU_WEST'],
            ['NORTH_AFRICA', 'EU_SOUTH'],
            ['NORTH_AFRICA', 'BRAZIL'],
            ['NORTH_AFRICA', 'EGYPT'],
            ['NORTH_AFRICA', 'EAST_AFRICA'],
            ['NORTH_AFRICA', 'CONGO'],
            ['AFGHANISTAN', 'CHINA'],
            ['AFGHANISTAN', 'INDIA'],
            ['AFGHANISTAN', 'MIDDLE_EAST'],
            ['AFGHANISTAN', 'URAL'],
            ['AFGHANISTAN', 'UKRAINE'],
            ['CHINA', 'INDIA'],
            ['CHINA', 'AFGHANISTAN'],
            ['CHINA', 'MONGOLIA'],
            ['CHINA', 'SIAM'],
            ['CHINA', 'SIBERIA'],
            ['CHINA', 'URAL'],
            ['INDIA', 'MIDDLE_EAST'],
            ['INDIA', 'CHINA'],
            ['INDIA', 'SIAM'],
            ['INDIA', 'AFGHANISTAN'],
            ['IRKUTSK', 'KAMCHATKA'],
            ['IRKUTSK', 'MONGOLIA'],
            ['IRKUTSK', 'SIBERIA'],
            ['IRKUTSK', 'YAKUTSK'],
            ['JAPAN', 'KAMCHATKA'],
            ['JAPAN', 'MONGOLIA'],
            ['KAMCHATKA', 'MONGOLIA'],
            ['KAMCHATKA', 'IRKUTSK'],
            ['KAMCHATKA', 'ALASKA'],
            ['KAMCHATKA', 'JAPAN'],
            ['KAMCHATKA', 'YAKUTSK'],
            ['MIDDLE_EAST', 'UKRAINE'],
            ['MIDDLE_EAST', 'AFGHANISTAN'],
            ['MIDDLE_EAST', 'INDIA'],
            ['MIDDLE_EAST', 'EGYPT'],
            ['MIDDLE_EAST', 'EU_SOUTH'],
            ['MIDDLE_EAST', 'EAST_AFRICA'],
            ['MONGOLIA', 'SIBERIA'],
            ['MONGOLIA', 'CHINA'],
            ['MONGOLIA', 'IRKUTSK'],
            ['MONGOLIA', 'JAPAN'],
            ['SIAM', 'INDONESIA'],
            ['SIAM', 'INDIA'],
            ['SIAM', 'CHINA'],
            ['SIBERIA', 'URAL'],
            ['SIBERIA', 'YAKUTSK'],
            ['SIBERIA', 'IRKUTSK'],
            ['SIBERIA', 'CHINA'],

            ['URAL', 'AFGHANISTAN'],
            ['URAL', 'UKRAINE'],
            ['URAL', 'CHINA'],
            ['URAL', 'SIBERIA'],
            ['EAST_AUSTRAL', 'NEW_GUINEA'],
            ['EAST_AUSTRAL', 'WEST_AUSTRALIA'],
            ['INDONESIA', 'NEW_GUINEA'],
            ['INDONESIA', 'SIAM'],
            ['INDONESIA', 'WEST_AUSTRALIA'],
            ['NEW_GUINEA', 'WEST_AUSTRALIA'],
            ['NEW_GUINEA', 'INDONESIA'],
            ['WEST_AUSTRALIA', 'EAST_AUSTRAL'],
            ['EAST_US', 'CARIBBEAN'],
            ['ONTARIO', 'GREENLAND'],
            ['EAST_US', 'USA_WEST'],
            ['EAST_US', 'ONTARIO'],
            ['EAST_US', 'QUEBEC'],
            ['BRAZIL', 'ARGENTINA'],
            ['ESCANDINAVIA', 'ICELAND'],
            ['EU_SOUTH', 'NORTH_EU'],
            ['SOUTH_AFRICA', 'CONGO'],
            ['EGYPT', 'EAST_AFRICA'],
            ['SOUTH_AFRICA', 'EAST_AFRICA'],
            ['SOUTH_AFRICA', 'MADAGASCAR'],
            ['YAKUTSK', 'IRKUTSK'],
            ['MONGOLIA', 'KAMCHATKA'],
            ['YAKUTSK', 'KAMCHATKA'],
            ['SIBERIA', 'MONGOLIA'],
            ['YAKUTSK', 'SIBERIA'],

            ['NEW_GUINEA', 'EAST_AUSTRAL'],
            ['WEST_AUSTRALIA', 'INDONESIA'],
            ['WEST_AUSTRALIA', 'NEW_GUINEA'],
        ];

        foreach ($fronteras as $frontera) {
            $pais1 = Pais::where('nom', $frontera[0])->first();
            $pais2 = Pais::where('nom', $frontera[1])->first();

            if ($pais1 && $pais2) {
                Frontera::firstOrCreate([
                    'pais1_id' => $pais1->id,
                    'pais2_id' => $pais2->id
                ]);
            } else {
                echo "Error: No se encontró uno de los países para la frontera {$frontera[0]} - {$frontera[1]}\n";
            }
        }


        DB::table('tipus_cartas')->insert([
            ['nom' => 'comodin'],
            ['nom' => 'artilleria'],
            ['nom' => 'infanteria'],
            ['nom' => 'caballeria'],
        ]);

        DB::table('cartas')->insert([
            ['tipus' => 1, 'pais_id' => null],
        ]);

        $artilleriaId = DB::table('tipus_cartas')->where('nom', 'artilleria')->first()->id;
        $infanteriaId = DB::table('tipus_cartas')->where('nom', 'infanteria')->first()->id;
        $caballeriaId = DB::table('tipus_cartas')->where('nom', 'caballeria')->first()->id;


        $territories = DB::table('pais')->get()->keyBy('nom');


        $territoryTypes = [
            // Artillería 
            'GREENLAND' => $artilleriaId,
            'ICELAND' => $artilleriaId,
            'INDIA' => $artilleriaId,
            'JAPAN' => $artilleriaId,
            'EGYPT' => $artilleriaId,
            'MADAGASCAR' => $artilleriaId,
            'EAST_AUSTRAL' => $artilleriaId,
            'MONGOLIA' => $artilleriaId,
            'NEW_GUINEA' => $artilleriaId,
            'NORTH_EU' => $artilleriaId,
            'NORTHWEST' => $artilleriaId,
            'PERU' => $artilleriaId,
            'SIAM' => $artilleriaId,
            'VENEZUELA' => $artilleriaId,

            // Caballería 
            'ALBERTA' => $caballeriaId,
            'ALASKA' => $caballeriaId,
            'CHINA' => $caballeriaId,
            'CONGO' => $caballeriaId,
            'EAST_US' => $caballeriaId,
            'IRKUTSK' => $caballeriaId,
            'KAMCHATKA' => $caballeriaId,
            'MIDDLE_EAST' => $caballeriaId,
            'ONTARIO' => $caballeriaId,
            'QUEBEC' => $caballeriaId,
            'SOUTH_AFRICA' => $caballeriaId,
            'USA_WEST' => $caballeriaId,
            'WEST_AUSTRALIA' => $caballeriaId,
            'YAKUTSK' => $caballeriaId,

            // INFANTERIA 
            'AFGHANISTAN' => $infanteriaId,
            'ARGENTINA' => $infanteriaId,
            'BRAZIL' => $infanteriaId,
            'CARIBBEAN' => $infanteriaId,
            'EAST_AFRICA' => $infanteriaId,
            'ESCANDINAVIA' => $infanteriaId,
            'EU_SOUTH' => $infanteriaId,
            'EU_WEST' => $infanteriaId,
            'GREAT_BRITAIN' => $infanteriaId,
            'INDONESIA' => $infanteriaId,
            'NORTH_AFRICA' => $infanteriaId,
            'SIBERIA' => $infanteriaId,
            'UKRAINE' => $infanteriaId,
            'URAL' => $infanteriaId,

        ];

        // Crear las cartas para cada territorio
        foreach ($territoryTypes as $territoryName => $tipoCarta) {
            if ($territory = $territories->get($territoryName)) {
                DB::table('cartas')->insert([
                    'tipus' => $tipoCarta,
                    'pais_id' => $territory->id, // Usamos el ID del territorio
                ]);
            }
        }
    }
}
