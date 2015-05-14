<?php

class TaxonomyTest extends TestCase {


	public function onlyKeys($array, $keys)
	{
		$result = [];

		if (empty($array))
			return $array;

		if (empty($keys))
			return $array;


		for ($i=0; $i<count($array); $i++) {
			$result[] = [];

			foreach ($keys as $key) {
				if (isset($array[$i][$key])) {
					$result[$i][$key] = $array[$i][$key];
				}
			}
		}

		return $result;
	}


	public function buildComparableString($array)
	{
		$result = '';

		if (!empty($array)) {
			foreach ($array as $i) {
				$result .= str_repeat("\t", $i['depth']) . "{$i['name']} ({$i['code']})\n";
			}
		}

		return $result;
	}



	public function testGetRoot()
	{
		$this->seed('TaxonomySeeder');

		$root = [ Taxonomy::root()->toArray() ];
		$result = $this->onlyKeys($root, ['id', 'name', 'code', 'left', 'right']);

		$expected = [
			[ 'id' => 1, 'name' => 'root', 'code' => 'root', 'left' => 1, 'right' => 2 ],
		];

		$this->assertTrue($expected == $result);
	}



	public function testLastTerm()
	{
		$comparable =  ['id', 'name', 'code', 'left', 'right'];
		$t = time();

		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expectHasNoChild = [];
		$resultHasNoChild = ($root->lastTerm() ? $root->lastTerm()->toArray() : []);

		$expectHasOneChild = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
		];


		DB::table('taxonomy')->insert([
			[ 'name' => 'A', 'code' => 'a', 'depth' => 1, 'left' => 2, 'right' => 3, 'created' => $t ],
		]);

		$root->right = 4;
		$root->save();

		$resultForOneChild = [ Taxonomy::root()->lastTerm()->toArray() ];
		$resultForOneChild = $this->onlyKeys($resultForOneChild, $comparable);


		$this->seed('TaxonomySeeder');
		$root = Taxonomy::root();

		$expectHasMultipleChildren = [
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 8, 'right' => 9 ],
		];

		$root->right = 10;
		$root->save();

		DB::table('taxonomy')->insert([
			[ 'name' => 'A', 'code' => 'a', 'depth' => 1, 'left' => 2, 'right' => 3, 'created' => $t ],
			[ 'name' => 'B', 'code' => 'b', 'depth' => 1, 'left' => 4, 'right' => 5, 'created' => $t ],
			[ 'name' => 'C', 'code' => 'c', 'depth' => 1, 'left' => 6, 'right' => 7, 'created' => $t ],
			[ 'name' => 'D', 'code' => 'd', 'depth' => 1, 'left' => 8, 'right' => 9, 'created' => $t ],
		]);

		$resultHasMultipleChildren = [ Taxonomy::root()->lastTerm()->toArray() ];
		$resultHasMultipleChildren = $this->onlyKeys($resultHasMultipleChildren, $comparable);


		$this->seed('TaxonomySeeder');
		$root = Taxonomy::root();

		$expectTermWithChildren = [
			[ 'id' => 5, 'name' => 'B2', 'code' => 'b2', 'left' => 7, 'right' => 8 ],
		];

		$root->right = 14;
		$root->save();

		DB::table('taxonomy')->insert([
			[ 'name' => 'A', 'code' => 'a', 'depth' => 1, 'left' => 2, 'right' => 3, 'created' => $t ],
			[ 'name' => 'B', 'code' => 'b', 'depth' => 1, 'left' => 4, 'right' => 9, 'created' => $t ],
			[ 'name' => 'B1', 'code' => 'b1', 'depth' => 2, 'left' => 5, 'right' => 6, 'created' => $t ],
			[ 'name' => 'B2', 'code' => 'b2', 'depth' => 2, 'left' => 7, 'right' => 8, 'created' => $t ],
			[ 'name' => 'C', 'code' => 'c', 'depth' => 1, 'left' => 10, 'right' => 11, 'created' => $t ],
			[ 'name' => 'D', 'code' => 'd', 'depth' => 1, 'left' => 12, 'right' => 13, 'created' => $t ],
		]);

		$resultTermWithChildren = [ Taxonomy::findByCode('b')->lastTerm()->toArray() ];
		$resultTermWithChildren = $this->onlyKeys($resultTermWithChildren, $comparable);


		$this->assertTrue(
			$expectHasNoChild === $resultHasNoChild
			&& $expectHasOneChild === $resultForOneChild
			&& $expectHasMultipleChildren === $resultHasMultipleChildren
			&& $expectTermWithChildren == $resultTermWithChildren
		);

	}



	/**
	 *
	 */
	public function testAddTerm()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 5  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 6, 'right' => 7  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 8, 'right' => 9  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue($expect === $result);
	}



	/**
	 * Move a term to right after the another term that at its right.
	 *
	 * For instance, move 'F' after 'B':
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A    B    C    D    E   (F)   G    |    A    B   (F)   C    D    E    G
	 *         ^----------------+         |
	 */
	public function testMoveLeftAtSameLevel()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 5  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 6, 'right' => 7  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 8, 'right' => 9  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 10, 'right' => 11  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 12, 'right' => 13  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 14, 'right' => 15  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		Taxonomy::findByCode('f')->moveTo(Taxonomy::ROOT_CODE, 'b');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term to right after the another term that at its left.
	 *
	 * For instance, move 'B' after 'F':
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A   (B)   C    D    E    F    G    |    A    C    D    E    F   (B)   G
	 *      +---------------------^       |
	 */
	public function testMoveRightAtSameLevel()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 11  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 12, 'right' => 13  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 14, 'right' => 15  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		Taxonomy::findByCode('b')->moveTo(Taxonomy::ROOT_CODE, 'f');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its left.
	 *
	 * For instance, move 'F' after 'B':
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A    B    C    D    E   (F)   G    |    A    B   (F)   C    D    E    G
	 *                        F1 F2       |            F1 F2
	 *         ^----------------+         |
	 */
	public function testMoveLeftWithChildrenAtSameLevel()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 5  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 6, 'right' => 11  ],
				[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 7, 'right' => 8  ],
				[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 9, 'right' => 10  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 12, 'right' => 13  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 14, 'right' => 15  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 16, 'right' => 17  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 18, 'right' => 19  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');

		Taxonomy::findByCode('f')->moveTo(Taxonomy::ROOT_CODE, 'b');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its right.
	 *
	 * For instance, move 'B' after 'F':
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A   (B)   C    D    E    F    G    |    A    C    D    E    F   (B)   G
	 *    B1 B2                           |                           B1 B2
	 *      +---------------------^       |
	 */
	public function testMoveRightWithChildrenAtSameLevel()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 11  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 12, 'right' => 17  ],
				[ 'id' => 9,  'name' => 'B1', 'code' => 'b1', 'left' => 13, 'right' => 14  ],
				[ 'id' => 10, 'name' => 'B2', 'code' => 'b2', 'left' => 15, 'right' => 16  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 18, 'right' => 19  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');

		Taxonomy::findByCode('b')->moveTo(Taxonomy::ROOT_CODE, 'f');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}




	/**
	 * Move a term that has no child, into a specified term that has no child at the left side,
	 *
	 * For instance, move 'B' into 'F' as a descendant:
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A    B    C    D    E   (F)   G    |    A    B    C    D    E    G
	 *      ^-------------------+         |        (F)
	 */
	public function testDescentToTermHasNoChildFromRight()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 7  ],
				[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 5, 'right' => 6  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 8, 'right' => 9  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 10, 'right' => 11  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 12, 'right' => 13  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 14, 'right' => 15  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		Taxonomy::findByCode('f')->moveTo('b');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has no child, into a specified term that has no child at the right side,
	 *
	 * For instance, move 'B' into 'F' as a descendant:
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A   (B)   C    D    E    F    G    |    A    C    D    E    F    G
	 *      +-------------------^         |                       (B)
	 */
	public function testDescentToTermHasNoChildFromLeft()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 13  ],
				[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 11, 'right' => 12  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 14, 'right' => 15  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		Taxonomy::findByCode('b')->moveTo('f');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at source's right.
	 *
	 * For instance, move 'F' into 'B', as a descendant of 'B'
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A    B    C    D    E   (F)   G    |    A    B    C    D    E    G
	 *                        F1 F2       |        (F)
	 *      ^-------------------+         |       F2 F2
	 */
	public function testDescentWithChildrenToTermHasNoChildFromRight()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 11  ],
				[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 5, 'right' => 10  ],
					[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 6, 'right' => 7  ],
					[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 8, 'right' => 9  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 12, 'right' => 13  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 14, 'right' => 15  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 16, 'right' => 17  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 18, 'right' => 19  ],
		];

		$root->addTerm('A', 'a');
		$root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');

		Taxonomy::findByCode('f')->moveTo('b');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at source's left.
	 *
	 * For instance, move 'B' into 'F', as a descendant of 'F'
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A   (B)   C    D    E    F    G    |    A    C    D    E    F    G
	 *    B1 B2                           |                       (B)
	 *      +-------------------^         |                      B1 B2
	 */
	public function testDescentWithChildrenToTermHasNoChildFromLeft()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 17  ],
				[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 11, 'right' => 16  ],
					[ 'id' => 9,  'name' => 'B1', 'code' => 'b1', 'left' => 12, 'right' => 13  ],
					[ 'id' => 10, 'name' => 'B2', 'code' => 'b2', 'left' => 14, 'right' => 15  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 18, 'right' => 19  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');

		Taxonomy::findByCode('b')->moveTo('f');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its right.
	 *
	 * For instance, move 'B' into 'F', as a descendant of 'F'
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A   (B)   C    D    E    F    G    |    A    C    D    E       F         G
	 *    B1 B2               F1 F2       |                       F1   F2  (B)
	 *      +------------------------^    |                               B2 B2
	 */
	public function testDescentWithChildrenToTermHasChildrenFromLeft()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 21  ],
				[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 11, 'right' => 12  ],
				[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 13, 'right' => 14  ],
				[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 15, 'right' => 20  ],
					[ 'id' => 11, 'name' => 'B1', 'code' => 'b1', 'left' => 16, 'right' => 17  ],
					[ 'id' => 12, 'name' => 'B2', 'code' => 'b2', 'left' => 18, 'right' => 19  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 22, 'right' => 23  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');

		Taxonomy::findByCode('b')->moveTo('f');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its left.
	 *
	 * For instance, move 'F' into 'B', as a descendant of 'B'
	 *
	 * Before:                            |    After:
	 *              root                  |               root
	 * A    B    C    D    E   (F)   G    |    A        B             C    D    E    G
	 *    B1 B2               F1 F2       |          B1   B2  (F)
	 *           ^--------------+         |                  F2 F2
	 */
	public function testDescentWithChildrenToTermHasChildrenFromRight()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 15  ],
				[ 'id' => 11, 'name' => 'B1', 'code' => 'b1', 'left' => 5, 'right' => 6  ],
				[ 'id' => 12, 'name' => 'B2', 'code' => 'b2', 'left' => 7, 'right' => 8  ],
				[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 9, 'right' => 14  ],
					[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 10, 'right' => 11  ],
					[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 12, 'right' => 13  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 16, 'right' => 17  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 18, 'right' => 19  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 20, 'right' => 21  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 22, 'right' => 23  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');

		Taxonomy::findByCode('f')->moveTo('b');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its right.
	 *
	 * For instance, move 'B' after 'F2', which is a descendant of 'F'
	 *
	 * Before:                               |    After:
	 *              root                     |                   root
	 * A   (B)   C    D    E     F      G    |    A    C    D    E           F          G
	 *    B1 B2               F1 F2 F3       |                       F1   F2  (B)  F3
	 *      +----------------------^         |                               B2 B2
	 */
	public function testDescentWithChildrenToSpecifiedTermChildFromLeft()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 4, 'right' => 5  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 6, 'right' => 7  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 8, 'right' => 9  ],
			[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 10, 'right' => 23  ],
				[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 11, 'right' => 12  ],
				[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 13, 'right' => 14  ],
				[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 15, 'right' => 20  ],
					[ 'id' => 12, 'name' => 'B1', 'code' => 'b1', 'left' => 16, 'right' => 17  ],
					[ 'id' => 13, 'name' => 'B2', 'code' => 'b2', 'left' => 18, 'right' => 19  ],
				[ 'id' => 11, 'name' => 'F3', 'code' => 'f3', 'left' => 21, 'right' => 22  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 24, 'right' => 25  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');
		$f->addTerm('F3', 'f3');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');

		Taxonomy::findByCode('b')->moveTo('f', 'f2');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



	/**
	 * Move a term that has children, to right after the another term that at its right.
	 *
	 * For instance, move 'F' after 'B2', which is a descendant of 'B'
	 *
	 * Before:                                     |    After:
	 *              root                           |                           root
	 * A       B        C    D    E    (F)    G    |    A          B             C    D    E    F    G
	 *    B1   B2   B2                F1 F2        |        B1   B2   (F)   B3
	 *            ^---------------------+          |                 F2 F2
	 */
	public function testDescentWithChildrenToSpecifiedTermChildFromRight()
	{
		$this->seed('TaxonomySeeder');

		$root = Taxonomy::root();

		$expect = [
			[ 'id' => 2, 'name' => 'A', 'code' => 'a', 'left' => 2, 'right' => 3  ],
			[ 'id' => 3, 'name' => 'B', 'code' => 'b', 'left' => 4, 'right' => 17  ],
				[ 'id' => 11, 'name' => 'B1', 'code' => 'b1', 'left' => 5, 'right' => 6  ],
				[ 'id' => 12, 'name' => 'B2', 'code' => 'b2', 'left' => 7, 'right' => 8  ],
				[ 'id' => 7, 'name' => 'F', 'code' => 'f', 'left' => 9, 'right' => 14  ],
					[ 'id' => 9,  'name' => 'F1', 'code' => 'f1', 'left' => 10, 'right' => 11  ],
					[ 'id' => 10, 'name' => 'F2', 'code' => 'f2', 'left' => 12, 'right' => 13  ],
				[ 'id' => 13, 'name' => 'B3', 'code' => 'b3', 'left' => 15, 'right' => 16  ],
			[ 'id' => 4, 'name' => 'C', 'code' => 'c', 'left' => 18, 'right' => 19  ],
			[ 'id' => 5, 'name' => 'D', 'code' => 'd', 'left' => 20, 'right' => 21  ],
			[ 'id' => 6, 'name' => 'E', 'code' => 'e', 'left' => 22, 'right' => 23  ],
			[ 'id' => 8, 'name' => 'G', 'code' => 'g', 'left' => 24, 'right' => 25  ],
		];

		$root->addTerm('A', 'a');
		$b = $root->addTerm('B', 'b');
		$root->addTerm('C', 'c');
		$root->addTerm('D', 'd');
		$root->addTerm('E', 'e');
		$f = $root->addTerm('F', 'f');
		$root->addTerm('G', 'g');

		$f->addTerm('F1', 'f1');
		$f->addTerm('F2', 'f2');

		$b->addTerm('B1', 'b1');
		$b->addTerm('B2', 'b2');
		$b->addTerm('B3', 'b3');

		Taxonomy::findByCode('f')->moveTo('b', 'b2');

		$result = Taxonomy::root()->children()->toArray();
		$result = $this->onlyKeys($result, ['id', 'name', 'code', 'left', 'right']);

		$this->assertTrue( $expect === $result );
	}



}

