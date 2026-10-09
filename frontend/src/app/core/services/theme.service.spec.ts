import { TestBed } from '@angular/core/testing';
import { ThemeService } from './theme.service';

describe('ThemeService', () => {
  let service: ThemeService;

  beforeEach(() => {
    localStorage.clear();
    document.documentElement.classList.remove('dark-theme', 'light-theme');
    TestBed.configureTestingModule({});
    service = TestBed.inject(ThemeService);
  });

  afterEach(() => {
    document.documentElement.classList.remove('dark-theme', 'light-theme');
    localStorage.clear();
  });

  it('applies dark-theme class for dark', () => {
    service.apply('dark');

    expect(document.documentElement.classList.contains('dark-theme')).toBeTrue();
    expect(document.documentElement.classList.contains('light-theme')).toBeFalse();
    expect(service.current()).toBe('dark');
  });

  it('applies light-theme class for light', () => {
    service.apply('light');

    expect(document.documentElement.classList.contains('light-theme')).toBeTrue();
    expect(document.documentElement.classList.contains('dark-theme')).toBeFalse();
  });

  it('removes both classes for system (falls back to prefers-color-scheme)', () => {
    service.apply('dark');
    service.apply('system');

    expect(document.documentElement.classList.contains('dark-theme')).toBeFalse();
    expect(document.documentElement.classList.contains('light-theme')).toBeFalse();
  });

  it('caches the applied theme in localStorage', () => {
    service.apply('dark');

    expect(localStorage.getItem('cc-theme')).toBe('dark');
  });

  it('falls back to system when cache write throws (storage bloqueado)', () => {
    spyOn(localStorage, 'setItem').and.throwError('QuotaExceededError');

    expect(() => service.apply('dark')).not.toThrow();
    expect(document.documentElement.classList.contains('dark-theme')).toBeTrue();
  });

  it('starts on system when the cached value is invalid', () => {
    localStorage.setItem('cc-theme', 'not-a-real-theme');
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({});

    const fresh = TestBed.inject(ThemeService);

    expect(fresh.current()).toBe('system');
  });

  it('starts on system when reading the cache throws (storage bloqueado)', () => {
    spyOn(localStorage, 'getItem').and.throwError('SecurityError');
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({});

    const fresh = TestBed.inject(ThemeService);

    expect(fresh.current()).toBe('system');
  });
});
