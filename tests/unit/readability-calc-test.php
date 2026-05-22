<?php
/**
 * Unit tests for CURAI_Readability_Calc.
 *
 * @package CuratorAI
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/audit/class-curai-readability-calc.php';

final class CURAI_Readability_Calc_Test extends TestCase {

	public function test_score_returns_zero_for_empty_text(): void {
		$result = CURAI_Readability_Calc::score( '' );
		$this->assertSame( 0.0, $result['flesch_kincaid'] );
		$this->assertSame( 0, $result['sentences'] );
		$this->assertSame( 0, $result['words'] );
	}

	public function test_score_counts_sentences_and_words(): void {
		$result = CURAI_Readability_Calc::score( 'The cat sat. The dog ran. They were friends.' );
		$this->assertSame( 3, $result['sentences'] );
		$this->assertSame( 9, $result['words'] );
	}

	public function test_score_returns_higher_for_simple_text(): void {
		$simple  = CURAI_Readability_Calc::score( 'See the cat. The cat is big. The cat is fat.' );
		$complex = CURAI_Readability_Calc::score( 'Notwithstanding the antecedent complications, the proliferation of multisyllabic terminology fundamentally diminishes comprehensibility.' );

		$this->assertGreaterThan( $complex['flesch_kincaid'], $simple['flesch_kincaid'] );
	}
}
