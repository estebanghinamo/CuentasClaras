import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { GeneralShellComponent } from './general-shell.component';

describe('GeneralShellComponent', () => {
  let component: GeneralShellComponent;
  let fixture: ComponentFixture<GeneralShellComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GeneralShellComponent],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(GeneralShellComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
