<?php
/**
 * Pure-PHP readability metrics (Flesch-Kincaid + sentence stats).
 *
 * @package CuratorAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Calculates Flesch Reading Ease scores without external dependencies.
 *
 * Flesch Reading Ease = 206.835 − 1.015 × (words/sentences) − 84.6 × (syllables/words).
 * Higher score = easier to read. 60–70 is the target band for general audiences.
 *
 * @since 1.0.0
 */
class CURAI_Readability_Calc {

	/**
	 * Compute readability stats for a plain-text string.
	 *
	 * @since 1.0.0
	 * @param string $text Plain text (no HTML).
	 * @return array{ flesch_kincaid: float, grade: float, sentences: int, words: int, syllables: int, avg_sentence_len: float, passive_ratio: float }
	 */
	public static function score( string $text ): array {
		$text = trim( $text );

		if ( '' === $text ) {
			return array(
				'flesch_kincaid'   => 0.0,
				'grade'            => 0.0,
				'sentences'        => 0,
				'words'            => 0,
				'syllables'        => 0,
				'avg_sentence_len' => 0.0,
				'passive_ratio'    => 0.0,
			);
		}

		$sentences = self::count_sentences( $text );
		$words     = self::tokenize_words( $text );
		$word_n    = count( $words );
		$syllables = 0;
		foreach ( $words as $word ) {
			$syllables += self::syllables_in( $word );
		}

		$flesch = 0.0;
		$grade  = 0.0;
		if ( $sentences > 0 && $word_n > 0 ) {
			$flesch = 206.835 - 1.015 * ( $word_n / $sentences ) - 84.6 * ( $syllables / $word_n );
			$grade  = 0.39 * ( $word_n / $sentences ) + 11.8 * ( $syllables / $word_n ) - 15.59;
		}

		$avg_sentence_len = $sentences > 0 ? $word_n / $sentences : 0.0;
		$passive_ratio    = self::passive_ratio( $text, $sentences );

		return array(
			'flesch_kincaid'   => round( $flesch, 1 ),
			'grade'            => round( $grade, 1 ),
			'sentences'        => $sentences,
			'words'            => $word_n,
			'syllables'        => $syllables,
			'avg_sentence_len' => round( $avg_sentence_len, 1 ),
			'passive_ratio'    => round( $passive_ratio, 2 ),
		);
	}

	/**
	 * Count sentence-ending punctuation as a proxy for sentences.
	 *
	 * @since 1.0.0
	 * @param string $text Plain text.
	 * @return int
	 */
	private static function count_sentences( string $text ): int {
		preg_match_all( '/[.!?]+/', $text, $matches );
		return isset( $matches[0] ) ? count( $matches[0] ) : 0;
	}

	/**
	 * Split text into word tokens (alphabetic only).
	 *
	 * @since 1.0.0
	 * @param string $text Plain text.
	 * @return array<int, string>
	 */
	private static function tokenize_words( string $text ): array {
		preg_match_all( "/[A-Za-z']+/", $text, $matches );
		return $matches[0] ?? array();
	}

	/**
	 * Estimate syllables in a word by counting vowel groups.
	 *
	 * Drops a trailing silent "e" and clamps to a minimum of 1.
	 *
	 * @since 1.0.0
	 * @param string $word Word.
	 * @return int
	 */
	private static function syllables_in( string $word ): int {
		$word = strtolower( $word );
		$word = preg_replace( '/e$/', '', $word );
		preg_match_all( '/[aeiouy]+/', (string) $word, $matches );
		$count = isset( $matches[0] ) ? count( $matches[0] ) : 0;
		return max( 1, $count );
	}

	/**
	 * Rough passive-voice ratio: count "was/were + past participle" patterns
	 * relative to sentence count. Heuristic, not linguistic.
	 *
	 * @since 1.0.0
	 * @param string $text      Plain text.
	 * @param int    $sentences Sentence count.
	 * @return float Ratio between 0.0 and 1.0.
	 */
	private static function passive_ratio( string $text, int $sentences ): float {
		if ( $sentences <= 0 ) {
			return 0.0;
		}
		preg_match_all( '/\b(was|were|been|being|is|are|be)\s+\w+ed\b/i', $text, $matches );
		$hits = isset( $matches[0] ) ? count( $matches[0] ) : 0;
		return min( 1.0, $hits / $sentences );
	}
}
